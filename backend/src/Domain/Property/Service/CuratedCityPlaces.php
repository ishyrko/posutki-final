<?php

declare(strict_types=1);

namespace App\Domain\Property\Service;

final class CuratedCityPlaces
{
    public const PLACE_TYPE_MICRODISTRICT = 'microdistrict';

    public const PLACE_TYPE_RESIDENTIAL_COMPLEX = 'residential_complex';

    /**
     * @return list<array{
     *     citySlug: string,
     *     type: self::PLACE_TYPE_*,
     *     officialName: string,
     *     name: string,
     *     namePrepositional: string
     * }>
     */
    public static function all(): array
    {
        return [
            // Brest microdistricts
            ['citySlug' => 'brest', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'Интендантский Городок', 'name' => 'Интендантский Городок', 'namePrepositional' => 'Интендантском Городке'],
            ['citySlug' => 'brest', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Восток', 'name' => 'Восток', 'namePrepositional' => 'Востоке'],
            ['citySlug' => 'brest', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Юго-Запад', 'name' => 'Юго-Запад', 'namePrepositional' => 'Юго-Западе'],
            ['citySlug' => 'brest', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'Северный городок', 'name' => 'Северный городок', 'namePrepositional' => 'Северном городке'],
            ['citySlug' => 'brest', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Юго-Восток', 'name' => 'Юго-Восток', 'namePrepositional' => 'Юго-Востоке'],

            // Grodno microdistricts
            ['citySlug' => 'grodno', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Девятовка', 'name' => 'Девятовка', 'namePrepositional' => 'Девятовке'],
            ['citySlug' => 'grodno', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Принеманский', 'name' => 'Принеманский', 'namePrepositional' => 'Принеманском'],
            ['citySlug' => 'grodno', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Форты', 'name' => 'Форты', 'namePrepositional' => 'Фортах'],

            // Minsk microdistricts
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Чкаловский', 'name' => 'Чкаловский', 'namePrepositional' => 'Чкаловском'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Золотая Горка', 'name' => 'Золотая Горка', 'namePrepositional' => 'Золотой Горке'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Уручье', 'name' => 'Уручье', 'namePrepositional' => 'Уручье'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Брилевичи', 'name' => 'Брилевичи', 'namePrepositional' => 'Брилевичах'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Восток', 'name' => 'Восток', 'namePrepositional' => 'Востоке'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_MICRODISTRICT, 'officialName' => 'микрорайон Комаровка', 'name' => 'Комаровка', 'namePrepositional' => 'Комаровке'],

            // Minsk residential complexes
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_RESIDENTIAL_COMPLEX, 'officialName' => 'экспериментальный многофункциональный комплекс Минск-Мир', 'name' => 'Минск-Мир', 'namePrepositional' => 'Минск-Мире'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_RESIDENTIAL_COMPLEX, 'officialName' => 'ЖК Маяк Минска', 'name' => 'Маяк Минска', 'namePrepositional' => 'Маяке Минска'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_RESIDENTIAL_COMPLEX, 'officialName' => 'ЖК Vogue', 'name' => 'Vogue', 'namePrepositional' => 'Vogue'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_RESIDENTIAL_COMPLEX, 'officialName' => 'ЖК Грушевский Посад', 'name' => 'Грушевский Посад', 'namePrepositional' => 'Грушевском Посаде'],
            ['citySlug' => 'minsk', 'type' => self::PLACE_TYPE_RESIDENTIAL_COMPLEX, 'officialName' => 'ЖК Мегаполис', 'name' => 'Мегаполис', 'namePrepositional' => 'Мегаполисе'],
        ];
    }
}
