<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Command\Property\ArchiveProperty\ArchivePropertyCommand;
use App\Application\Command\Property\UnarchiveProperty\UnarchivePropertyCommand;
use App\Application\Command\Property\BoostProperty\BoostPropertyCommand;
use App\Application\Command\Property\CreateProperty\CreatePropertyCommand;
use App\Application\Command\Property\PublishProperty\PublishPropertyCommand;
use App\Application\Command\Property\UpdateProperty\UpdatePropertyCommand;
use App\Application\Command\Property\CreateAvailabilityBlock\CreateAvailabilityBlockCommand;
use App\Application\Command\Property\DeleteAvailabilityBlock\DeleteAvailabilityBlockCommand;
use App\Application\Command\Property\DeleteProperty\DeletePropertyCommand;
use App\Application\Command\Property\RegenerateCalendarExportToken\RegenerateCalendarExportTokenCommand;
use App\Application\Query\Property\GetHomeCityApartmentCounts\GetHomeCityApartmentCountsQuery;
use App\Application\Query\Property\GetOwnerCalendar\GetOwnerCalendarQuery;
use App\Application\Query\Property\GetHomeRegionHouseCounts\GetHomeRegionHouseCountsQuery;
use App\Application\Query\Property\GetOwnerListings\GetOwnerListingsQuery;
use App\Application\Query\Property\GetProperty\GetPropertyQuery;
use App\Application\Query\Property\SearchProperties\SearchPropertiesQuery;
use App\Application\Command\CommandBusInterface;
use App\Application\Query\QueryBusInterface;
use App\Application\Service\PropertyCalendarAggregator;
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
        private readonly PropertyCalendarAggregator $propertyCalendarAggregator,
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

        $roomsList = self::parseRoomsQueryList($request->query->get('rooms'));
        $guests = self::parseGuestsQuery($request->query->get('guests'));

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
            rooms: $roomsList,
            metroStationId: $request->query->getInt('metroStationId') ?: null,
            nearMetro: $request->query->getBoolean('nearMetro', false),
            guests: $guests,
            sortBy: $request->query->get('sortBy', 'createdAt'),
            sortOrder: $request->query->get('sortOrder', 'DESC'),
            page: $request->query->getInt('page', 1),
            limit: $request->query->getInt('limit', 20),
        );

        $result = $this->queryBus->ask($query);

        return $this->json(ApiResponse::paginated(
            $result['items'],
            $result['total'],
            $result['page'],
            $result['limit'],
        ));
    }

    #[Route('/home-city-apartment-counts', name: 'home_city_apartment_counts', methods: ['GET'])]
    public function homeCityApartmentCounts(): JsonResponse
    {
        $counts = $this->queryBus->ask(new GetHomeCityApartmentCountsQuery());

        return $this->json(ApiResponse::success($counts));
    }

    #[Route('/home-region-house-counts', name: 'home_region_house_counts', methods: ['GET'])]
    public function homeRegionHouseCounts(): JsonResponse
    {
        $counts = $this->queryBus->ask(new GetHomeRegionHouseCountsQuery());

        return $this->json(ApiResponse::success($counts));
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

    #[Route('/{id}/owner-listings', name: 'owner_listings', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function ownerListings(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        $viewerUserId = $user !== null ? (string) $user->getId()->getValue() : null;
        $limit = min(max($request->query->getInt('limit', 10), 1), 10);
        $query = new GetOwnerListingsQuery(
            propertyId: $id,
            viewerUserId: $viewerUserId,
            limit: $limit,
        );
        $properties = $this->queryBus->ask($query);

        return $this->json(
            ApiResponse::success($properties),
            Response::HTTP_OK,
            ['Cache-Control' => 'public, max-age=300'],
        );
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
            paymentMethods: $request->paymentMethods,
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
            streetName: $request->streetName,
            block: $request->block,
            images: $request->images,
            amenities: $request->amenities,
            sellerType: $request->sellerType,
            weekendPriceNegotiable: $request->weekendPriceNegotiable,
            additionalServices: $request->additionalServices,
            instagramUrl: $request->instagramUrl,
            websiteUrl: $request->websiteUrl,
            videoUrl: $request->videoUrl,
            externalCalendarUrls: $request->externalCalendarUrls,
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
            paymentMethods: $request->paymentMethods,
            maxDailyGuests: $request->maxDailyGuests,
            dailySingleBeds: $request->dailySingleBeds,
            dailyDoubleBeds: $request->dailyDoubleBeds,
            checkInTime: $request->checkInTime,
            checkOutTime: $request->checkOutTime,
            building: $request->building,
            block: $request->block,
            cityId: $request->cityId,
            streetId: $request->streetId,
            streetName: $request->streetName,
            latitude: $request->coordinates['latitude'] ?? null,
            longitude: $request->coordinates['longitude'] ?? null,
            images: $request->images,
            amenities: $request->amenities,
            sellerType: $request->sellerType,
            weekendPriceNegotiable: $request->weekendPriceNegotiable,
            additionalServices: $request->additionalServices,
            instagramUrl: $request->instagramUrl,
            websiteUrl: $request->websiteUrl,
            videoUrl: $request->videoUrl,
            externalCalendarUrls: $request->externalCalendarUrls,
        );

        $requiresModeration = $this->commandBus->dispatch($command) === true;

        return $this->json(
            ApiResponse::success([
                'message' => $requiresModeration
                    ? 'Изменения отправлены на модерацию'
                    : 'Объявление успешно обновлено',
                'requiresModeration' => $requiresModeration,
            ])
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

    #[Route('/{id}/archive', name: 'archive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function archive(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new ArchivePropertyCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
        );

        $archivedAt = $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['archivedAt' => $archivedAt])
        );
    }

    #[Route('/{id}/unarchive', name: 'unarchive', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function unarchive(string $id, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $command = new UnarchivePropertyCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
        );

        $this->commandBus->dispatch($command);

        return $this->json(
            ApiResponse::success(['message' => 'Объявление снова опубликовано'])
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

    #[Route('/{id}/calendar', name: 'calendar', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function calendar(string $id): JsonResponse
    {
        $property = $this->propertyRepository->findById(Id::fromString($id));
        if ($property === null) {
            throw new DomainException('Объявление не найдено');
        }

        $calendarData = $this->propertyCalendarAggregator->getPublicCalendarData($property);

        return $this->json(
            ApiResponse::success($calendarData),
            Response::HTTP_OK,
            ['Cache-Control' => 'public, max-age=1800'],
        );
    }

    #[Route('/{id}/availability', name: 'availability', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function availability(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $data = $this->queryBus->ask(new GetOwnerCalendarQuery(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
            exportBaseUrl: $request->getSchemeAndHttpHost(),
        ));

        return $this->json(ApiResponse::success($data));
    }

    #[Route('/{id}/availability/block', name: 'availability_block_create', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function createAvailabilityBlock(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $payload = json_decode($request->getContent(), true);
        if (!\is_array($payload)) {
            return $this->json(ApiResponse::error('Некорректное тело запроса', 400), 400);
        }

        $block = $this->commandBus->dispatch(new CreateAvailabilityBlockCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
            startDate: (string) ($payload['startDate'] ?? $payload['start'] ?? ''),
            endDate: (string) ($payload['endDate'] ?? $payload['end'] ?? ''),
            note: isset($payload['note']) ? (string) $payload['note'] : null,
        ));

        return $this->json(ApiResponse::success($block), Response::HTTP_CREATED);
    }

    #[Route('/{id}/availability/block/{blockId}', name: 'availability_block_delete', methods: ['DELETE'], requirements: ['id' => '\d+', 'blockId' => '\d+'])]
    public function deleteAvailabilityBlock(string $id, string $blockId, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $this->commandBus->dispatch(new DeleteAvailabilityBlockCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
            blockId: $blockId,
        ));

        return $this->json(ApiResponse::success(['deleted' => true]));
    }

    #[Route('/{id}/calendar/export-token', name: 'calendar_export_token_regenerate', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function regenerateCalendarExportToken(string $id, Request $request, #[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json(ApiResponse::error('Требуется авторизация', 401), 401);
        }

        $token = $this->commandBus->dispatch(new RegenerateCalendarExportTokenCommand(
            propertyId: $id,
            userId: (string) $user->getId()->getValue(),
        ));

        return $this->json(ApiResponse::success([
            'exportToken' => $token,
            'exportUrl' => $request->getSchemeAndHttpHost() . '/ical/' . $token . '.ics',
        ]));
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

    /**
     * @return list<int>|null distinct 1–4; 3+ expands to 3 and 4; 4 means «four or more» rooms
     */
    private static function parseRoomsQueryList(mixed $raw): ?array
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        if (\is_int($raw)) {
            $raw = (string) $raw;
        }

        if (!\is_string($raw)) {
            return null;
        }

        $out = [];
        foreach (preg_split('/\s*,\s*/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $part) {
            $part = trim($part);
            if ($part === '3+') {
                $out[] = 3;
                $out[] = 4;
                continue;
            }
            if ($part === '4+') {
                $out[] = 4;
                continue;
            }
            $n = (int) $part;
            if ($n >= 1 && $n <= 4) {
                $out[] = $n;
            }
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out === [] ? null : $out;
    }

    private static function parseGuestsQuery(mixed $raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        $n = \is_int($raw) ? $raw : (int) (string) $raw;
        if ($n < 1 || $n > 20) {
            return null;
        }

        return $n;
    }
}
