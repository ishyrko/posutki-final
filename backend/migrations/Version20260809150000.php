<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Yandex-geocoder verified landmark coordinate/address fixes (follow-up after Version20260809140000).
 */
final class Version20260809150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix landmark coordinates/addresses verified via Yandex Geocoder (Minsk Arena, stations, squares, etc.)';
    }

    public function up(Schema $schema): void
    {
        foreach ($this->fixes() as $fix) {
            $this->addSql(
                'UPDATE landmarks l
                 INNER JOIN cities c ON c.id = l.city_id
                 SET l.latitude = ?, l.longitude = ?, l.address = ?
                 WHERE c.slug = ? AND l.slug = ?',
                [
                    $fix['latitude'],
                    $fix['longitude'],
                    $fix['address'],
                    $fix['city'],
                    $fix['slug'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->write('Irreversible data fix: previous coordinates/addresses are not restored.');
    }

    /**
     * @return list<array{city: string, slug: string, latitude: float, longitude: float, address: string}>
     */
    private function fixes(): array
    {
        return [
            [
                'city' => 'minsk',
                'slug' => 'minsk-arena',
                'latitude' => 53.935930,
                'longitude' => 27.481638,
                'address' => 'пр. Победителей, 111, Минск',
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
                'slug' => 'vokzal-minsk-passazhirskiy',
                'latitude' => 53.890760,
                'longitude' => 27.551006,
                'address' => 'пл. Привокзальная, 5, Минск',
            ],
            [
                'city' => 'minsk',
                'slug' => 'tc-galileo',
                'latitude' => 53.890128,
                'longitude' => 27.554689,
                'address' => 'ул. Бобруйская, 6, Минск',
            ],
            [
                'city' => 'minsk',
                'slug' => 'park-chelyuskintsev',
                'latitude' => 53.921144,
                'longitude' => 27.617634,
                'address' => 'пр. Независимости, 84/1, Минск',
            ],
            [
                'city' => 'minsk',
                'slug' => 'ploshchad-nezavisimosti',
                'latitude' => 53.8938,
                'longitude' => 27.5474,
                'address' => 'пл. Независимости, Минск',
            ],
            [
                'city' => 'baranovichi',
                'slug' => 'vokzal-baranovichi',
                'latitude' => 53.130655,
                'longitude' => 25.988585,
                'address' => 'ул. Вильчковского, 5А, Барановичи',
            ],
            [
                'city' => 'baranovichi',
                'slug' => 'kraevedcheskiy-muzey',
                'latitude' => 53.134308,
                'longitude' => 26.014663,
                'address' => 'ул. Советская, 72, Барановичи',
            ],
            [
                'city' => 'bobruysk',
                'slug' => 'bobruyskaya-krepost',
                'latitude' => 53.137851,
                'longitude' => 29.245058,
                'address' => 'Бобруйская крепость, Бобруйск',
            ],
            [
                'city' => 'bobruysk',
                'slug' => 'dramaticheskiy-teatr',
                'latitude' => 53.134475,
                'longitude' => 29.227020,
                'address' => 'ул. Социалистическая, 85, Бобруйск',
            ],
            [
                'city' => 'bobruysk',
                'slug' => 'ploshchad-lenina',
                'latitude' => 53.145538,
                'longitude' => 29.225556,
                'address' => 'пл. Ленина, Бобруйск',
            ],
            [
                'city' => 'brest',
                'slug' => 'brestskaya-krepost',
                'latitude' => 52.083682,
                'longitude' => 23.658184,
                'address' => 'ул. Героев обороны Брестской крепости, 60, Брест',
            ],
            [
                'city' => 'brest',
                'slug' => 'ploshchad-lenina',
                'latitude' => 52.0943,
                'longitude' => 23.6847,
                'address' => 'пл. Ленина, Брест',
            ],
            [
                'city' => 'gomel',
                'slug' => 'dvorets-rumyantsevyh-i-paskevichey',
                'latitude' => 52.422227,
                'longitude' => 31.016877,
                'address' => 'пл. Ленина, 4, Гомель',
            ],
            [
                'city' => 'gomel',
                'slug' => 'vokzal-gomel',
                'latitude' => 52.432002,
                'longitude' => 30.992506,
                'address' => 'пл. Привокзальная, 1, Гомель',
            ],
            [
                'city' => 'gomel',
                'slug' => 'ploshchad-lenina',
                'latitude' => 52.424600,
                'longitude' => 31.014362,
                'address' => 'пл. Ленина, Гомель',
            ],
            [
                'city' => 'krichev',
                'slug' => 'vokzal-krichev',
                'latitude' => 53.739039,
                'longitude' => 31.711042,
                'address' => 'пл. Привокзальная, Кричев',
            ],
            [
                'city' => 'mogilev',
                'slug' => 'ploshchad-zvezd',
                'latitude' => 53.902257,
                'longitude' => 30.340814,
                'address' => 'пл. Звёзд, Могилёв',
            ],
            [
                'city' => 'molodechno',
                'slug' => 'ploshchad-tsentralnaya',
                'latitude' => 54.306376,
                'longitude' => 26.837537,
                'address' => 'пл. Центральная, Молодечно',
            ],
            [
                'city' => 'orsha',
                'slug' => 'vokzal-orsha',
                'latitude' => 54.519082,
                'longitude' => 30.369641,
                'address' => 'ул. Заслонова, 3А, Орша',
            ],
            [
                'city' => 'pinsk',
                'slug' => 'iezuitskiy-kollegium',
                'latitude' => 52.113320,
                'longitude' => 26.107872,
                'address' => 'ул. Ленина, 23, Пинск',
            ],
            [
                'city' => 'pinsk',
                'slug' => 'dvorets-butrimovicha',
                'latitude' => 52.114249,
                'longitude' => 26.110558,
                'address' => 'ул. Ленина, 37, Пинск',
            ],
            [
                'city' => 'svetlogorsk',
                'slug' => 'vokzal-svetlogorsk',
                'latitude' => 52.630913,
                'longitude' => 29.715039,
                'address' => 'пл. Привокзальная, Светлогорск',
            ],
            [
                'city' => 'zhlobin',
                'slug' => 'vokzal-zhlobin',
                'latitude' => 52.890669,
                'longitude' => 30.035288,
                'address' => 'пл. Привокзальная, 2, Жлобин',
            ],
        ];
    }
}
