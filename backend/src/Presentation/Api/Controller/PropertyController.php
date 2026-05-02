<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\Property\BoostProperty\BoostPropertyCommand;
use App\Application\Command\Property\CreateProperty\CreatePropertyCommand;
use App\Application\Command\Property\PublishProperty\PublishPropertyCommand;
use App\Application\Command\Property\UpdateProperty\UpdatePropertyCommand;
use App\Application\Command\Property\DeleteProperty\DeletePropertyCommand;
use App\Application\Query\Property\GetProperty\GetPropertyQuery;
use App\Application\Query\Property\SearchProperties\SearchPropertiesQuery;
use App\Application\Command\CommandBusInterface;
use App\Application\Query\QueryBusInterface;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Repository\PropertyDailyStatRepositoryInterface;
use App\Domain\Property\Enum\PropertyType;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\Exception\DomainException;
use App\Domain\Shared\ValueObject\Id;
use App\Presentation\Api\Request\CreatePropertyRequest;
use App\Presentation\Api\Request\UpdatePropertyRequest;
use App\Presentation\Api\Response\ApiResponse;
use App\Domain\User\Entity\User;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/properties', name: 'api_properties_')]
class PropertyController extends AbstractController
{
    public function __construct(
        private readonly CommandBusInterface $commandBus,
        private readonly QueryBusInterface $queryBus,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyDailyStatRepositoryInterface $propertyDailyStatRepository,
        private readonly FavoriteRepositoryInterface $favoriteRepository,
    ) {
    }

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(Request $request): JsonResponse
    {
        $typesList = null;
        $typesRaw = $request->query->get('types');
        if (is_string($typesRaw) && $typesRaw !== '') {
            $allowed = array_fill_keys(PropertyType::values(), true);
            $parsed = [];
            foreach (explode(',', $typesRaw) as $part) {
                $t = trim($part);
                if ($t !== '' && isset($allowed[$t])) {
                    $parsed[] = $t;
                }
            }
            $parsed = array_values(array_unique($parsed));
            $typesList = $parsed !== [] ? $parsed : null;
        }

        $query = new SearchPropertiesQuery(
            type: $request->query->get('type'),
            types: $typesList,
            dealType: $request->query->get('dealType'),
            regionSlug: $request->query->get('regionSlug'),
            citySlug: $request->query->get('citySlug'),
            cityId: $request->query->getInt('cityId') ?: null,
            minPrice: $request->query->getInt('minPrice') ?: null,
            maxPrice: $request->query->getInt('maxPrice') ?: null,
            priceType: $request->query->get('priceType', 'total'),
            currency: $request->query->get('currency'),
            minArea: $request->query->get('minArea') ? (float) $request->query->get('minArea') : null,
            maxArea: $request->query->get('maxArea') ? (float) $request->query->get('maxArea') : null,
            rooms: $request->query->getInt('rooms') ?: null,
            metroStationId: $request->query->getInt('metroStationId') ?: null,
            nearMetro: $request->query->getBoolean('nearMetro', false),
            sortBy: $request->query->get('sortBy', 'createdAt'),
            sortOrder: $request->query->get('sortOrder', 'DESC'),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
        );

        $properties = $this->queryBus->ask($query);

        return $this->json(ApiResponse::success($properties));
    }

    #[Route('/my', name: 'my', methods: ['GET'])]
    public function my(Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $query = new \App\Application\Query\Property\GetMyProperties\GetMyPropertiesQuery(
            userId: (string) $user->getId()->getValue(),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
        );

        $properties = $this->queryBus->ask($query);

        return $this->json(ApiResponse::success($properties));
    }

    #[Route('/{id}', name: 'get', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function get(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        $viewerUserId = $user !== null ? (string) $user->getId()->getValue() : null;
        $query = new GetPropertyQuery($id, $viewerUserId);
        $property = $this->queryBus->ask($query);

        return $this->json(ApiResponse::success($property));
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(CreatePropertyRequest $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new CreatePropertyCommand(
            ownerId: (string) $user->getId()->getValue(),
            type: $request->type,
            dealType: $request->dealType,
            title: $request->title,
            description: $request->description,
            priceAmount: (int) round((float) $request->price['amount']),
            priceCurrency: $request->price['currency'] ?? 'BYN',
            area: $request->area,
            landArea: $request->landArea,
            rooms: $request->rooms,
            floor: $request->floor,
            totalFloors: $request->totalFloors,
            bathrooms: $request->bathrooms,
            yearBuilt: $request->yearBuilt,
            renovation: $request->renovation,
            balcony: $request->balcony,
            livingArea: $request->livingArea,
            kitchenArea: $request->kitchenArea,
            roomsInDeal: $request->roomsInDeal,
            roomsArea: $request->roomsArea,
            dealConditions: $request->dealConditions,
            maxDailyGuests: $request->maxDailyGuests,
            dailySingleBeds: $request->dailySingleBeds,
            dailyDoubleBeds: $request->dailyDoubleBeds,
            checkInTime: $request->checkInTime,
            checkOutTime: $request->checkOutTime,
            building: $request->building,
            cityId: $request->cityId,
            latitude: $request->coordinates['latitude'],
            longitude: $request->coordinates['longitude'],
            streetId: $request->streetId,
            block: $request->block,
            images: $request->images,
            amenities: $request->amenities,
            sellerType: $request->sellerType,
        );

        $propertyId = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success([
                'message' => 'Объявление отправлено на модерацию',
                'propertyId' => $propertyId,
            ]),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}/publish', name: 'publish', methods: ['POST'])]
    public function publish(string $id): JsonResponse
    {
        $userId = 'temp-user-id';

        $command = new PublishPropertyCommand(
            propertyId: $id,
            userId: $userId,
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Объявление успешно опубликовано'])
        );
    }

    #[Route('/{id}', name: 'update', methods: ['PUT', 'PATCH'])]
    public function update(
        string $id,
        UpdatePropertyRequest $request,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new UpdatePropertyCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
            type: $request->type,
            dealType: $request->dealType,
            title: $request->title,
            description: $request->description,
            priceAmount: isset($request->price['amount'])
                ? (int) round((float) $request->price['amount'])
                : null,
            priceCurrency: $request->price['currency'] ?? null,
            area: $request->area,
            landArea: $request->landArea,
            rooms: $request->rooms,
            floor: $request->floor,
            totalFloors: $request->totalFloors,
            bathrooms: $request->bathrooms,
            yearBuilt: $request->yearBuilt,
            renovation: $request->renovation,
            balcony: $request->balcony,
            livingArea: $request->livingArea,
            kitchenArea: $request->kitchenArea,
            roomsInDeal: $request->roomsInDeal,
            roomsArea: $request->roomsArea,
            dealConditions: $request->dealConditions,
            maxDailyGuests: $request->maxDailyGuests,
            dailySingleBeds: $request->dailySingleBeds,
            dailyDoubleBeds: $request->dailyDoubleBeds,
            checkInTime: $request->checkInTime,
            checkOutTime: $request->checkOutTime,
            building: $request->building,
            block: $request->block,
            cityId: $request->cityId,
            streetId: $request->streetId,
            latitude: $request->coordinates['latitude'] ?? null,
            longitude: $request->coordinates['longitude'] ?? null,
            images: $request->images,
            amenities: $request->amenities,
            sellerType: $request->sellerType,
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Объявление успешно обновлено'])
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(
        string $id,
        #[CurrentUser] ?User $user
    ): JsonResponse {
        if (!$user) {
            return $this->json(
                ApiResponse::error('Требуется авторизация', 401),
                401
            );
        }

        $command = new DeletePropertyCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Объявление успешно удалено'])
        );
    }

    #[Route('/{id}/boost', name: 'boost', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function boost(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new BoostPropertyCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
        );

        $boostedAt = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['boostedAt' => $boostedAt])
        );
    }

    #[Route('/{id}/phone-view', name: 'phone_view', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function trackPhoneView(string $id): JsonResponse
    {
        $property = $this->propertyRepository->findById(Id::fromString($id));
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        $property->incrementPhoneViews();
        $this->propertyRepository->save($property);
        $this->propertyDailyStatRepository->upsertPhoneView($property->getId()->getValue(), new \DateTimeImmutable());

        return $this->json(ApiResponse::success(['tracked' => true]));
    }

    #[Route('/{id}/stats', name: 'stats', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function stats(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $property = $this->propertyRepository->findById(Id::fromString($id));
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }
        if (!$property->isOwnedBy((string) $user->getId()->getValue())) {
            return $this->json(ApiResponse::error('Доступ запрещён', 403), 403);
        }

        $requestedPeriod = $request->query->getInt('period', 30);
        $period = in_array($requestedPeriod, [7, 30, 90], true) ? $requestedPeriod : 30;

        $dailyPropertyStats = $this->propertyDailyStatRepository->findByPropertyAndPeriod($property->getId()->getValue(), $period);
        $dailyFavoritesStats = $this->favoriteRepository->findDailyCountsByProperty(Id::fromString($id), $period);

        $today = new \DateTimeImmutable('today');
        $startDate = $today->modify(sprintf('-%d days', $period - 1));
        $dailyByDate = [];

        for ($day = 0; $day < $period; $day++) {
            $date = $startDate->modify(sprintf('+%d days', $day))->format('Y-m-d');
            $dailyByDate[$date] = ['date' => $date, 'views' => 0, 'phoneViews' => 0, 'favorites' => 0];
        }

        foreach ($dailyPropertyStats as $row) {
            $date = $row['date'];
            if (!isset($dailyByDate[$date])) {
                continue;
            }

            $dailyByDate[$date]['views'] = (int) $row['views'];
            $dailyByDate[$date]['phoneViews'] = (int) $row['phoneViews'];
        }

        foreach ($dailyFavoritesStats as $row) {
            $date = $row['date'];
            if (!isset($dailyByDate[$date])) {
                continue;
            }

            $dailyByDate[$date]['favorites'] = (int) $row['count'];
        }

        $daily = array_values($dailyByDate);
        $totals = [
            'views' => array_sum(array_column($daily, 'views')),
            'phoneViews' => array_sum(array_column($daily, 'phoneViews')),
            'favorites' => array_sum(array_column($daily, 'favorites')),
        ];

        return $this->json(ApiResponse::success([
            'property' => [
                'id' => $property->getId()->getValue(),
                'title' => $property->getTitle(),
            ],
            'period' => $period,
            'totals' => $totals,
            'daily' => $daily,
        ]));
    }
}
