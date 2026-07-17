<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Property\CreatePlacementPurchase\CreatePlacementPurchaseCommand;
use App\Application\DTO\PlacementPurchaseDTO;
use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Entity\PropertyPlacementLevelPrice;
use App\Domain\Property\Entity\PropertyPlacementScopeSettings;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyPlacementLevelPriceRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementScopeSettingsRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Entity\User;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

final class PlacementController extends AbstractController
{
    public function __construct(
        private readonly PropertyPlacementLevelPriceRepositoryInterface $levelPriceRepository,
        private readonly PropertyPlacementScopeSettingsRepositoryInterface $scopeSettingsRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementService $placementService,
        private readonly CommandBusInterface $commandBus,
    ) {
    }

    #[Route('/api/placement/levels', name: 'api_placement_levels', methods: ['GET'])]
    public function levels(Request $request): JsonResponse
    {
        $propertyType = $request->query->getString('propertyType', PropertyType::Apartment->value);
        if (!in_array($propertyType, PropertyType::values(), true)) {
            return $this->json(ApiResponse::error('Некорректный propertyType', 400), 400);
        }

        if ($propertyType === PropertyType::House->value) {
            $regionId = $request->query->getInt('regionId');
            if ($regionId <= 0) {
                return $this->json(ApiResponse::error('Укажите regionId', 400), 400);
            }

            $levelPrices = $this->levelPriceRepository->findActiveByRegionId($regionId);
        } else {
            $cityId = $request->query->getInt('cityId');
            if ($cityId <= 0) {
                return $this->json(ApiResponse::error('Укажите cityId', 400), 400);
            }

            $levelPrices = $this->levelPriceRepository->findActiveByCityId($cityId);
        }

        return $this->json(ApiResponse::success(array_map(
            fn (PropertyPlacementLevelPrice $levelPrice) => $this->levelPriceToArray($levelPrice),
            $levelPrices,
        )));
    }

    #[Route('/api/placement/scope', name: 'api_placement_scope', methods: ['GET'])]
    public function scope(Request $request): JsonResponse
    {
        $propertyType = $request->query->getString('propertyType', PropertyType::Apartment->value);
        if (!in_array($propertyType, PropertyType::values(), true)) {
            return $this->json(ApiResponse::error('Некорректный propertyType', 400), 400);
        }

        if ($propertyType === PropertyType::House->value) {
            $regionId = $request->query->getInt('regionId');
            if ($regionId <= 0) {
                return $this->json(ApiResponse::error('Укажите regionId', 400), 400);
            }

            $settings = $this->scopeSettingsRepository->findActiveByRegionId($regionId);
        } else {
            $cityId = $request->query->getInt('cityId');
            if ($cityId <= 0) {
                return $this->json(ApiResponse::error('Укажите cityId', 400), 400);
            }

            $settings = $this->scopeSettingsRepository->findActiveByCityId($cityId);
        }

        if ($settings === null) {
            return $this->json(ApiResponse::success([
                'propertyType' => $propertyType,
                'maxLevel' => PropertyPlacementScopeSettings::DEFAULT_MAX_LEVEL,
            ]));
        }

        return $this->json(ApiResponse::success([
            'propertyType' => $settings->getPropertyType(),
            'cityId' => $settings->getCityId(),
            'regionId' => $settings->getRegionId(),
            'maxLevel' => $settings->getMaxLevel(),
        ]));
    }

    #[Route('/api/properties/{id}/placement-purchases/quote', name: 'api_property_placement_purchases_quote', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function quotePurchase(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $property = $this->propertyRepository->findById(Id::fromString($id));
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }
        if (!$property->getOwnerId()->equals($user->getId())) {
            throw new DomainException('Нет прав на это объявление');
        }

        $level = $request->query->getInt('level');
        $durationMonths = $request->query->getInt('durationMonths');

        if (
            $level < PropertyPlacementLevelPrice::MIN_LEVEL
            || $level > PropertyPlacementLevelPrice::MAX_LEVEL
        ) {
            return $this->json(ApiResponse::error(sprintf(
                'Укажите VIP-уровень от %d до %d',
                PropertyPlacementLevelPrice::MIN_LEVEL,
                PropertyPlacementLevelPrice::MAX_LEVEL,
            ), 400), 400);
        }

        if ($durationMonths <= 0 || !in_array($durationMonths, \App\Domain\Property\Entity\PropertyPlacementPurchase::ALLOWED_DURATIONS, true)) {
            return $this->json(ApiResponse::error('Допустимый срок: 1, 3, 6 или 12 месяцев', 400), 400);
        }

        $levelPrice = null;
        foreach ($this->placementService->findLevelPricesForProperty($property) as $candidate) {
            if ($candidate->getLevel() === $level) {
                $levelPrice = $candidate;
                break;
            }
        }

        if ($levelPrice === null) {
            throw new DomainException('Для этого VIP-уровня и локации тариф не задан');
        }

        $quote = $this->placementService->quoteLevelPurchase($property, $levelPrice, $durationMonths);

        $currentLevel = $property->getPlacementBaseLevel();
        $currentExpiresAt = $property->getPlacementLevelExpiresAt();
        if ($currentLevel <= 0 || $currentExpiresAt === null) {
            $currentLevel = null;
            $currentExpiresAt = null;
        }

        return $this->json(ApiResponse::success([
            'mode' => $quote['mode'],
            'priceByn' => $quote['priceByn'],
            'currentLevel' => $currentLevel,
            'currentExpiresAt' => $currentExpiresAt?->format('c'),
            'targetExpiresAt' => $quote['expiresAtPreview']?->format('c'),
        ]));
    }

    #[Route('/api/properties/{id}/placement-purchases', name: 'api_property_placement_purchases_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createPurchase(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!is_array($payload)) {
            return $this->json(ApiResponse::error('Некорректное тело запроса', 400), 400);
        }

        $kind = isset($payload['kind']) && is_string($payload['kind']) ? $payload['kind'] : '';
        $level = isset($payload['level']) ? (int) $payload['level'] : null;
        $durationMonths = isset($payload['durationMonths']) ? (int) $payload['durationMonths'] : null;

        $result = $this->commandBus->dispatch(new CreatePlacementPurchaseCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
            kind: $kind,
            level: $level,
            durationMonths: $durationMonths,
        ));

        return $this->json(ApiResponse::success($result), 201);
    }

    #[Route('/api/properties/{id}/placement-purchases', name: 'api_property_placement_purchases_list', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function listPurchases(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $property = $this->propertyRepository->findById(Id::fromString($id));
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }
        if (!$property->getOwnerId()->equals($user->getId())) {
            throw new DomainException('Нет прав на это объявление');
        }

        $purchases = $this->purchaseRepository->findByPropertyId((int) $id);
        $data = [];
        foreach ($purchases as $purchase) {
            $levelPrice = $purchase->getLevelPriceId() !== null
                ? $this->levelPriceRepository->findById($purchase->getLevelPriceId())
                : null;
            $data[] = PlacementPurchaseDTO::fromEntity($purchase, $levelPrice, $property->getTitle());
        }

        return $this->json(ApiResponse::success($data));
    }

    #[Route('/api/placement-purchases', name: 'api_placement_purchases_list', methods: ['GET'])]
    public function listMyPurchases(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $purchases = $this->purchaseRepository->findByOwnerId($user->getId());
        $propertyTitles = [];
        $data = [];
        foreach ($purchases as $purchase) {
            $propertyId = $purchase->getPropertyId();
            if (!array_key_exists($propertyId, $propertyTitles)) {
                $property = $this->propertyRepository->findById(Id::fromInt($propertyId));
                $propertyTitles[$propertyId] = $property?->getTitle();
            }

            $levelPrice = $purchase->getLevelPriceId() !== null
                ? $this->levelPriceRepository->findById($purchase->getLevelPriceId())
                : null;
            $data[] = PlacementPurchaseDTO::fromEntity($purchase, $levelPrice, $propertyTitles[$propertyId]);
        }

        return $this->json(ApiResponse::success($data));
    }

    #[Route('/api/placement-purchases/pending-count', name: 'api_placement_purchases_pending_count', methods: ['GET'])]
    public function pendingCount(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $count = $this->purchaseRepository->countPendingPaymentByOwnerId($user->getId());

        return $this->json(ApiResponse::success(['pendingCount' => $count]));
    }

    #[Route('/api/placement-purchases/{id}', name: 'api_placement_purchase_get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function getPurchase(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $purchase = $this->purchaseRepository->findById((int) $id);
        if ($purchase === null) {
            throw new DomainException('Заявка не найдена');
        }
        if (!$purchase->getOwnerId()->equals($user->getId())) {
            throw new DomainException('Нет прав на эту заявку');
        }

        $property = $this->propertyRepository->findById(Id::fromInt($purchase->getPropertyId()));
        $levelPrice = $purchase->getLevelPriceId() !== null
            ? $this->levelPriceRepository->findById($purchase->getLevelPriceId())
            : null;

        return $this->json(ApiResponse::success(
            PlacementPurchaseDTO::fromEntity($purchase, $levelPrice, $property?->getTitle())
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function levelPriceToArray(PropertyPlacementLevelPrice $levelPrice): array
    {
        $occupied = $this->placementService->getLevelPriceOccupancy($levelPrice);
        $capacity = $levelPrice->getCapacity();

        return [
            'id' => $levelPrice->getId(),
            'propertyType' => $levelPrice->getPropertyType(),
            'cityId' => $levelPrice->getCityId(),
            'regionId' => $levelPrice->getRegionId(),
            'level' => $levelPrice->getLevel(),
            'label' => $levelPrice->getLabel(),
            'capacity' => $capacity,
            'occupied' => $occupied,
            'available' => $capacity !== null ? max(0, $capacity - $occupied) : null,
            'priceBynPerMonth' => $levelPrice->getPriceBynPerMonth(),
        ];
    }
}
