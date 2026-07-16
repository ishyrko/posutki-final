<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\CommandBusInterface;
use App\Application\Command\Property\CreatePlacementPurchase\CreatePlacementPurchaseCommand;
use App\Application\DTO\PlacementPurchaseDTO;
use App\Application\Service\PropertyPlacementService;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementPurchaseRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementSlotRepositoryInterface;
use App\Domain\Property\Repository\PropertyPlacementStandardPriceRepositoryInterface;
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
        private readonly PropertyPlacementSlotRepositoryInterface $slotRepository,
        private readonly PropertyPlacementStandardPriceRepositoryInterface $standardPriceRepository,
        private readonly PropertyPlacementPurchaseRepositoryInterface $purchaseRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyPlacementService $placementService,
        private readonly CommandBusInterface $commandBus,
        private readonly CityRepositoryInterface $cityRepository,
    ) {
    }

    #[Route('/api/placement-slots', name: 'api_placement_slots', methods: ['GET'])]
    public function slots(Request $request): JsonResponse
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

            $slots = $this->slotRepository->findActiveByRegionId($regionId);
        } else {
            $cityId = $request->query->getInt('cityId');
            if ($cityId <= 0) {
                return $this->json(ApiResponse::error('Укажите cityId', 400), 400);
            }

            $slots = $this->slotRepository->findActiveByCityId($cityId);
        }

        $data = [];
        foreach ($slots as $slot) {
            $occupied = $this->placementService->getSlotOccupancy($slot);
            $capacity = $slot->getCapacity();
            $data[] = [
                'id' => $slot->getId(),
                'propertyType' => $slot->getPropertyType(),
                'cityId' => $slot->getCityId(),
                'regionId' => $slot->getRegionId(),
                'rankFrom' => $slot->getRankFrom(),
                'rankTo' => $slot->getRankTo(),
                'label' => $slot->getLabel(),
                'capacity' => $capacity,
                'occupied' => $occupied,
                'available' => max(0, $capacity - $occupied),
                'priceBynPerMonth' => $slot->getPriceBynPerMonth(),
                'isTopSlot' => $slot->isTopSlot(),
            ];
        }

        return $this->json(ApiResponse::success($data));
    }

    #[Route('/api/placement/standard-price', name: 'api_placement_standard_price', methods: ['GET'])]
    public function standardPrice(Request $request): JsonResponse
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

            $price = $this->standardPriceRepository->findActiveByRegionId($regionId);
            if ($price === null) {
                return $this->json(ApiResponse::success(null));
            }

            return $this->json(ApiResponse::success([
                'propertyType' => PropertyType::House->value,
                'regionId' => $price->getRegionId(),
                'priceBynPerMonth' => $price->getPriceBynPerMonth(),
            ]));
        }

        $cityId = $request->query->getInt('cityId');
        if ($cityId <= 0) {
            return $this->json(ApiResponse::error('Укажите cityId', 400), 400);
        }

        $price = $this->standardPriceRepository->findActiveByCityId($cityId);
        if ($price === null) {
            return $this->json(ApiResponse::success(null));
        }

        return $this->json(ApiResponse::success([
            'propertyType' => PropertyType::Apartment->value,
            'cityId' => $price->getCityId(),
            'priceBynPerMonth' => $price->getPriceBynPerMonth(),
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

        $type = isset($payload['type']) && is_string($payload['type']) ? $payload['type'] : '';
        $durationMonths = isset($payload['durationMonths']) ? (int) $payload['durationMonths'] : 0;
        $slotId = isset($payload['slotId']) ? (int) $payload['slotId'] : null;
        if ($slotId !== null && $slotId <= 0) {
            $slotId = null;
        }

        $result = $this->commandBus->dispatch(new CreatePlacementPurchaseCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
            type: $type,
            durationMonths: $durationMonths,
            slotId: $slotId,
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
            $slot = $purchase->getSlotId() !== null
                ? $this->slotRepository->findById($purchase->getSlotId())
                : null;
            $data[] = PlacementPurchaseDTO::fromEntity($purchase, $slot, $property->getTitle());
        }

        return $this->json(ApiResponse::success($data));
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
        $slot = $purchase->getSlotId() !== null
            ? $this->slotRepository->findById($purchase->getSlotId())
            : null;

        return $this->json(ApiResponse::success(
            PlacementPurchaseDTO::fromEntity($purchase, $slot, $property?->getTitle())
        ));
    }
}
