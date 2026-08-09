<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Bulk-fix landmark coordinates/addresses and remove fake Vitebsk "Усадьба Суцких".
 */
final class Version20260809140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix landmark coordinates/addresses across cities; delete fake Vitebsk usadba-sutskikh';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->fixes() as $fix) {
            $sets = ['l.latitude = ?', 'l.longitude = ?', 'l.address = ?'];
            $params = [$fix['latitude'], $fix['longitude'], $fix['address']];

            foreach (['name', 'name_genitive', 'short_description', 'description'] as $field) {
                if (!isset($fix[$field])) {
                    continue;
                }
                $sets[] = 'l.' . $field . ' = ?';
                $params[] = $fix[$field];
            }

            $params[] = $fix['city'];
            $params[] = $fix['slug'];

            $this->addSql(
                'UPDATE landmarks l
                 INNER JOIN cities c ON c.id = l.city_id
                 SET ' . implode(', ', $sets) . '
                 WHERE c.slug = ? AND l.slug = ?',
                $params,
            );
        }

        $this->addSql(
            "DELETE pl FROM property_landmarks pl
             INNER JOIN landmarks l ON l.id = pl.landmark_id
             INNER JOIN cities c ON c.id = l.city_id
             WHERE c.slug = 'vitebsk' AND l.slug = 'usadba-sutskikh'"
        );
        $this->addSql(
            "DELETE l FROM landmarks l
             INNER JOIN cities c ON c.id = l.city_id
             WHERE c.slug = 'vitebsk' AND l.slug = 'usadba-sutskikh'"
        );
    }

    public function down(Schema $schema): void
    {
        $this->write('Irreversible data fix: previous coordinates/addresses and usadba-sutskikh are not restored.');
    }

    /**
     * @return list<array{
     *   city: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   address: string,
     *   name?: string,
     *   name_genitive?: string,
     *   short_description?: string,
     *   description?: string
     * }>
     */
    private function fixes(): array
    {
        return [
            [
                'city' => 'brest',
                'slug' => 'brestskaya-krepost',
                'latitude' => 52.087148,
                'longitude' => 23.654722,
                'address' => 'ул. Героев обороны Брестской крепости, 60, Брест',
            ],
            [
                'city' => 'brest',
                'slug' => 'ploshchad-lenina',
                'latitude' => 52.0943,
                'longitude' => 23.6847,
                'address' => 'пл. Ленина, Брест',
                'short_description' => 'Центральная площадь Бреста с памятником Ленину, зданием облисполкома и парадной застройкой исторического центра.',
                'description' => '<p>Площадь Ленина — одна из главных площадей Бреста на пересечении улиц Ленина, Пушкинской и Энгельса. Здесь проходят городские мероприятия; рядом кафе, магазины и прогулочные маршруты к пешеходной ул. Советской и набережной Мухавца.</p>',
            ],
            [
                'city' => 'brest',
                'slug' => 'muzey-berestie',
                'latitude' => 52.079998,
                'longitude' => 23.654624,
                'address' => 'проезд Крепостной, 15, Брест',
                'name' => 'Археологический музей «Берестье»',
                'name_genitive' => 'археологического музея «Берестье»',
                'short_description' => 'Археологический музей на месте раскопок средневекового городища XIII века в Брестской крепости.',
                'description' => '<p>Археологический музей «Берестье» показывает остатки деревянных построек древнего Бреста, найденные археологами. Экспозиция на территории крепости — логичное продолжение исторического маршрута.</p>',
            ],
            [
                'city' => 'minsk',
                'slug' => 'ostrov-slez',
                'latitude' => 53.909705,
                'longitude' => 27.554519,
                'address' => 'Троицкое предместье, остров Мужества и Скорби, Минск',
            ],
            [
                'city' => 'minsk',
                'slug' => 'park-gorkogo',
                'latitude' => 53.903350,
                'longitude' => 27.573144,
                'address' => 'ул. Янки Купалы, Минск',
            ],
            [
                'city' => 'minsk',
                'slug' => 'dvorets-sporta',
                'latitude' => 53.910734,
                'longitude' => 27.54974,
                'address' => 'пр. Победителей, 4, Минск',
            ],
            [
                'city' => 'vitebsk',
                'slug' => 'vokzal-vitebsk',
                'latitude' => 55.1949,
                'longitude' => 30.1858,
                'address' => 'ул. Космонавтов, 8, Витебск',
            ],
            [
                'city' => 'vitebsk',
                'slug' => 'dom-muzey-marka-shagala',
                'latitude' => 55.20036,
                'longitude' => 30.19056,
                'address' => 'ул. Покровская, 11, Витебск',
            ],
            [
                'city' => 'vitebsk',
                'slug' => 'ploshchad-pobedy',
                'latitude' => 55.183945,
                'longitude' => 30.203085,
                'address' => 'пл. Победы, Витебск',
            ],
            [
                'city' => 'grodno',
                'slug' => 'kolozhskaya-tserkov',
                'latitude' => 53.6784,
                'longitude' => 23.8186,
                'address' => 'ул. Коложа, 6, Гродно',
            ],
            [
                'city' => 'grodno',
                'slug' => 'vokzal-grodno',
                'latitude' => 53.6868,
                'longitude' => 23.8485,
                'address' => 'ул. Будённого, 37, Гродно',
            ],
            [
                'city' => 'grodno',
                'slug' => 'sobornyj-kostyol',
                'latitude' => 53.6783,
                'longitude' => 23.8315,
                'address' => 'пл. Советская, 4, Гродно',
            ],
            [
                'city' => 'grodno',
                'slug' => 'novyj-zamok',
                'latitude' => 53.6763,
                'longitude' => 23.8246,
                'address' => 'ул. Замковая, 20, Гродно',
            ],
            [
                'city' => 'mogilev',
                'slug' => 'vokzal-mogilev',
                'latitude' => 53.9261,
                'longitude' => 30.3383,
                'address' => 'пл. Привокзальная, 1, Могилёв',
            ],
            [
                'city' => 'mogilev',
                'slug' => 'nikolaevskiy-monastyr',
                'latitude' => 53.8938,
                'longitude' => 30.3458,
                'address' => 'ул. Никольская, 12, Могилёв',
            ],
            [
                'city' => 'baranovichi',
                'slug' => 'vokzal-baranovichi',
                'latitude' => 53.1322,
                'longitude' => 25.9935,
                'address' => 'ул. Вильчковского, 5А, Барановичи',
            ],
            [
                'city' => 'baranovichi',
                'slug' => 'kostyol-vozdvizheniya',
                'latitude' => 53.1292,
                'longitude' => 26.0037,
                'address' => 'ул. Куйбышева, 36, Барановичи',
            ],
            [
                'city' => 'pinsk',
                'slug' => 'vokzal-pinsk',
                'latitude' => 52.122,
                'longitude' => 26.0891,
                'address' => 'ул. Железнодорожная, 19, Пинск',
            ],
            [
                'city' => 'bobruysk',
                'slug' => 'bobruyskaya-krepost',
                'latitude' => 53.1351,
                'longitude' => 29.2407,
                'address' => 'Бобруйская крепость, Бобруйск',
            ],
            [
                'city' => 'bobruysk',
                'slug' => 'vokzal-bobruysk',
                'latitude' => 53.1378,
                'longitude' => 29.1955,
                'address' => 'ул. Железнодорожная, 13, Бобруйск',
            ],
            [
                'city' => 'molodechno',
                'slug' => 'kostyol-svyatogo-kazimira',
                'latitude' => 54.299935,
                'longitude' => 26.860483,
                'address' => 'ул. Великий Гостинец, 141, Молодечно',
            ],
            [
                'city' => 'molodechno',
                'slug' => 'vokzal-molodechno',
                'latitude' => 54.3146,
                'longitude' => 26.8394,
                'address' => 'пл. Привокзальная, 1, Молодечно',
            ],
            [
                'city' => 'logoysk',
                'slug' => 'gornolyzhnyy-kompleks-logoysk',
                'latitude' => 54.182778,
                'longitude' => 27.808333,
                'address' => 'Логойский с/с, 36 (трасса М3), Логойский район',
            ],
            [
                'city' => 'orsha',
                'slug' => 'vokzal-orsha',
                'latitude' => 54.5211,
                'longitude' => 30.3768,
                'address' => 'ул. Заслонова, 3А, Орша',
            ],
            [
                'city' => 'orsha',
                'slug' => 'kuteinskiy-monastyr',
                'latitude' => 54.4924,
                'longitude' => 30.4123,
                'address' => 'Кутеинский монастырь, Орша',
            ],
            [
                'city' => 'smorgon',
                'slug' => 'vokzal-smorgon',
                'latitude' => 54.4755,
                'longitude' => 26.3715,
                'address' => 'ул. Комсомольская, 105, Сморгонь',
            ],
            [
                'city' => 'volkovysk',
                'slug' => 'vokzal-volkovysk',
                'latitude' => 53.1385,
                'longitude' => 24.4156,
                'address' => 'ул. Аллейная, 14, Волковыск',
            ],
        ];
    }
}
