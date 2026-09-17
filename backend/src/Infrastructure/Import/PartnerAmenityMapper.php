<?php

declare(strict_types=1);

namespace App\Infrastructure\Import;

final class PartnerAmenityMapper
{
    /** @var array<string, string> normalized label => amenity id */
    private const MAP = [
        'холодильник' => 'fridge',
        'электроплита' => 'electric_stove',
        'электрическая плита' => 'electric_stove',
        'плита' => 'electric_stove',
        'газовая плита' => 'gas_stove',
        'индукция' => 'induction_stove',
        'индукционная плита' => 'induction_stove',
        'духовка' => 'oven',
        'духовой шкаф' => 'oven',
        'микроволновка' => 'microwave',
        'микроволновая печь' => 'microwave',
        'свч' => 'microwave',
        'посудомойка' => 'dishwasher',
        'посудомоечная машина' => 'dishwasher',
        'кофемашина' => 'coffee_machine',
        'чайник' => 'kettle',
        'электрочайник' => 'kettle',
        'блендер' => 'blender',
        'посуда' => 'dishes_utensils',
        'посуда и столовые приборы' => 'dishes_utensils',
        'раздельный санузел' => 'bathroom_separate',
        'раздельный' => 'bathroom_separate',
        'совмещенный санузел' => 'bathroom_combined',
        'совмещённый санузел' => 'bathroom_combined',
        'совмещенный' => 'bathroom_combined',
        'джакузи' => 'jacuzzi',
        'тропический душ' => 'rain_shower',
        'полотенца' => 'towels',
        'фен' => 'hairdryer',
        'халат' => 'bathrobes',
        'халаты' => 'bathrobes',
        'туалетные принадлежности' => 'toiletries',
        'smart tv' => 'smart_tv',
        'смарт тв' => 'smart_tv',
        'телевизор' => 'tv',
        'тв' => 'tv',
        'wifi' => 'wifi',
        'wi-fi' => 'wifi',
        'wi fi' => 'wifi',
        'вайфай' => 'wifi',
        'playstation' => 'playstation',
        'ps4' => 'playstation',
        'ps5' => 'playstation',
        'колонка' => 'bluetooth_speaker',
        'проектор' => 'projector',
        'кабельное' => 'cable_tv',
        'кондиционер' => 'air_conditioner',
        'теплый пол' => 'heated_floor',
        'тёплый пол' => 'heated_floor',
        'утюг' => 'iron',
        'стиральная машина' => 'washing_machine',
        'стиралка' => 'washing_machine',
        'сушильная машина' => 'dryer',
        'робот-пылесос' => 'robot_vacuum',
        'робот пылесос' => 'robot_vacuum',
        'детская кроватка' => 'crib',
        'стульчик для кормления' => 'high_chair',
        'парковка' => 'parking_open',
        'открытая парковка' => 'parking_open',
        'закрытая парковка' => 'parking_covered',
        'видеонаблюдение' => 'cctv',
        'беседка' => 'gazebo',
        'бассейн' => 'pool',
        'мангал' => 'bbq',
        'гриль' => 'bbq',
        'баня' => 'sauna',
        'сауна' => 'sauna',
        'детская площадка' => 'playground',
        'сад' => 'garden',
    ];

    /**
     * @param list<string> $labels
     *
     * @return list<string>
     */
    public function map(array $labels): array
    {
        $ids = [];
        foreach ($labels as $label) {
            $normalized = $this->normalize($label);
            if ($normalized === '') {
                continue;
            }
            if (isset(self::MAP[$normalized])) {
                $ids[] = self::MAP[$normalized];
                continue;
            }
            foreach (self::MAP as $needle => $id) {
                if (str_contains($normalized, $needle)) {
                    $ids[] = $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    private function normalize(string $label): string
    {
        $label = mb_strtolower(trim($label));
        $label = str_replace(['ё'], ['е'], $label);

        return preg_replace('/\s+/u', ' ', $label) ?? $label;
    }
}
