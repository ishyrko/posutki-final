<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Domain\Property\Entity\City;
use App\Domain\Property\Entity\CityDistrict;
use App\Domain\Property\Entity\CityMicrodistrict;
use App\Domain\Property\Entity\ResidentialComplex;
use App\Domain\Property\Repository\CityDistrictRepositoryInterface;
use App\Domain\Property\Repository\CityMicrodistrictRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Entity\CityRoomCatalogContent;
use App\Domain\Property\Repository\CityRoomCatalogContentRepositoryInterface;
use App\Domain\Property\Repository\ResidentialComplexRepositoryInterface;
use App\Domain\Property\Service\CitiesWithDistricts;
use App\Presentation\Api\Response\ApiResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/cities', name: 'api_cities_')]
class CityController extends AbstractController
{
    public function __construct(
        private readonly CityRepositoryInterface $cityRepository,
        private readonly CityDistrictRepositoryInterface $cityDistrictRepository,
        private readonly CityMicrodistrictRepositoryInterface $microdistrictRepository,
        private readonly ResidentialComplexRepositoryInterface $residentialComplexRepository,
        private readonly CityRoomCatalogContentRepositoryInterface $roomCatalogContentRepository,
    ) {
    }

    #[Route('/{slug}/districts', name: 'districts_by_city_slug', methods: ['GET'])]
    public function districtsByCitySlug(string $slug): JsonResponse
    {
        if (!CitiesWithDistricts::supportsSlug($slug)) {
            return $this->json(ApiResponse::success([]));
        }

        $city = $this->cityRepository->findBySlug($slug);
        if ($city === null) {
            return $this->json(ApiResponse::error('Город не найден', 404), 404);
        }

        $districts = $this->cityDistrictRepository->findAllByCityId($city->getId());

        return $this->json(ApiResponse::success(array_map(static fn (CityDistrict $district) => [
            'id' => $district->getId(),
            'name' => $district->getName(),
            'slug' => $district->getSlug(),
            'namePrepositional' => $district->getNamePrepositional(),
        ], $districts)));
    }

    #[Route('/{slug}/districts/{placeSlug}', name: 'district_by_city_slug', methods: ['GET'])]
    public function districtByCitySlug(string $slug, string $placeSlug): JsonResponse
    {
        $city = $this->requireCity($slug);
        if ($city instanceof JsonResponse) {
            return $city;
        }

        $district = $this->cityDistrictRepository->findByCityIdAndSlug($city->getId(), $placeSlug);
        if ($district === null) {
            return $this->json(ApiResponse::error('Район не найден', 404), 404);
        }

        return $this->json(ApiResponse::success($this->serializeCatalogPlace($district)));
    }

    #[Route('/{slug}/places', name: 'places_by_city_slug', methods: ['GET'])]
    public function placesByCitySlug(string $slug): JsonResponse
    {
        $city = $this->requireCity($slug);
        if ($city instanceof JsonResponse) {
            return $city;
        }

        $items = [];
        foreach ($this->microdistrictRepository->findAllByCityId($city->getId()) as $microdistrict) {
            $items[] = [
                'id' => $microdistrict->getId(),
                'type' => 'microdistrict',
                'name' => $microdistrict->getName(),
                'slug' => $microdistrict->getSlug(),
                'namePrepositional' => $microdistrict->getNamePrepositional(),
            ];
        }
        foreach ($this->residentialComplexRepository->findAllByCityId($city->getId()) as $complex) {
            $items[] = [
                'id' => $complex->getId(),
                'type' => 'residential_complex',
                'name' => $complex->getName(),
                'slug' => $complex->getSlug(),
                'namePrepositional' => $complex->getNamePrepositional(),
            ];
        }

        usort($items, static fn (array $a, array $b): int => strcmp($a['name'], $b['name']));

        return $this->json(ApiResponse::success($items));
    }

    #[Route('/{slug}/microdistricts/{placeSlug}', name: 'microdistrict_by_city_slug', methods: ['GET'])]
    public function microdistrictByCitySlug(string $slug, string $placeSlug): JsonResponse
    {
        $city = $this->requireCity($slug);
        if ($city instanceof JsonResponse) {
            return $city;
        }

        $microdistrict = $this->microdistrictRepository->findByCityIdAndSlug($city->getId(), $placeSlug);
        if ($microdistrict === null) {
            return $this->json(ApiResponse::error('Микрорайон не найден', 404), 404);
        }

        return $this->json(ApiResponse::success($this->serializeCatalogPlace($microdistrict)));
    }

    #[Route('/{slug}/residential-complexes/{placeSlug}', name: 'residential_complex_by_city_slug', methods: ['GET'])]
    public function residentialComplexByCitySlug(string $slug, string $placeSlug): JsonResponse
    {
        $city = $this->requireCity($slug);
        if ($city instanceof JsonResponse) {
            return $city;
        }

        $complex = $this->residentialComplexRepository->findByCityIdAndSlug($city->getId(), $placeSlug);
        if ($complex === null) {
            return $this->json(ApiResponse::error('Жилой комплекс не найден', 404), 404);
        }

        return $this->json(ApiResponse::success($this->serializeCatalogPlace($complex)));
    }

    #[Route('/{slug}/rooms/{roomsBucket}', name: 'room_catalog_by_city_slug', requirements: ['roomsBucket' => '[1-3]'], methods: ['GET'])]
    public function roomCatalogByCitySlug(string $slug, int $roomsBucket): JsonResponse
    {
        $city = $this->requireCity($slug);
        if ($city instanceof JsonResponse) {
            return $city;
        }

        $content = $this->roomCatalogContentRepository->findByCityIdAndRoomsBucket($city->getId(), $roomsBucket);
        if ($content === null) {
            return $this->json(ApiResponse::error('Страница по комнатам не найдена', 404), 404);
        }

        return $this->json(ApiResponse::success([
            'roomsBucket' => $content->getRoomsBucket(),
            ...$this->serializeCatalogSeoContent($content),
        ]));
    }

    #[Route('/{slug}', name: 'get_by_slug', methods: ['GET'])]
    public function getBySlug(string $slug): JsonResponse
    {
        $city = $this->cityRepository->findBySlug($slug);

        if ($city === null) {
            return $this->json(ApiResponse::error('Город не найден', 404), 404);
        }

        $district = $city->getRegionDistrict();
        $region = $district?->getRegion();

        return $this->json(ApiResponse::success([
            'id' => $city->getId(),
            'name' => $city->getName(),
            'slug' => $city->getSlug(),
            'shortName' => $city->getShortName(),
            'namePrepositional' => $city->getNamePrepositional(),
            'nameGenitive' => $city->getNameGenitive(),
            'latitude' => $city->getLatitude(),
            'longitude' => $city->getLongitude(),
            'isMain' => $city->isMain(),
            ...$this->serializeCatalogSeoContent($city),
            'district' => $district ? [
                'id' => $district->getId(),
                'name' => $district->getName(),
            ] : null,
            'region' => $region ? [
                'id' => $region->getId(),
                'name' => $region->getName(),
            ] : null,
        ]));
    }

    private function requireCity(string $slug): \App\Domain\Property\Entity\City|JsonResponse
    {
        $city = $this->cityRepository->findBySlug($slug);
        if ($city === null) {
            return $this->json(ApiResponse::error('Город не найден', 404), 404);
        }

        return $city;
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeCatalogPlace(CityDistrict|CityMicrodistrict|ResidentialComplex $place): array
    {
        return [
            'id' => $place->getId(),
            'name' => $place->getName(),
            'slug' => $place->getSlug(),
            'officialName' => $place->getOfficialName(),
            'namePrepositional' => $place instanceof CityDistrict
                ? $place->getNamePrepositional()
                : $place->getNamePrepositional(),
            ...$this->serializeCatalogSeoContent($place),
        ];
    }

    /**
     * @return array{catalogSeoVisible: bool, catalogSeoText: ?string, faq: list<array{question: string, answer: string}>}
     */
    private function serializeCatalogSeoContent(
        City|CityDistrict|CityMicrodistrict|ResidentialComplex|CityRoomCatalogContent $entity,
    ): array {
        $visible = $entity->isCatalogSeoVisible();

        return [
            'catalogSeoVisible' => $visible,
            'catalogSeoText' => $visible ? $entity->getCatalogSeoText() : null,
            'faq' => $visible ? ($entity->getCatalogFaq() ?? []) : [],
        ];
    }
}
