<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Domain\Property\Entity\Landmark;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\LandmarkRepositoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-demo-landmarks',
    description: 'Upsert demo landmarks for regional cities (inactive by default)',
)]
final class SeedDemoLandmarksCommand extends Command
{
    /** @var list<string> */
    private const CITY_SLUGS = [
        'minsk',
        'brest',
        'vitebsk',
        'gomel',
        'grodno',
        'mogilev',
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly LandmarkRepositoryInterface $landmarkRepository,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $total = 0;
        $skippedCities = [];

        foreach (self::landmarksByCitySlug() as $citySlug => $landmarks) {
            $city = $this->cityRepository->findBySlug($citySlug);
            if ($city === null) {
                $skippedCities[] = $citySlug;
                continue;
            }

            foreach ($landmarks as $data) {
                $this->upsertLandmark($city->getId(), $data);
                ++$total;
            }
        }

        $this->entityManager->flush();

        if ($skippedCities !== []) {
            $io->warning(sprintf(
                'Города не найдены в справочнике, пропущены: %s',
                implode(', ', $skippedCities),
            ));
        }

        $io->success(sprintf(
            'Достопримечательности обновлены: %d шт. в %d городах (выключены). Включите нужные в админке и запустите make sync-landmark-proximity.',
            $total,
            count(self::landmarksByCitySlug()) - count($skippedCities),
        ));

        return Command::SUCCESS;
    }

    /**
     * @param array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * } $data
     */
    private function upsertLandmark(int $cityId, array $data): void
    {
        $existing = $this->landmarkRepository->findAnyByCityIdAndSlug($cityId, $data['slug']);
        if ($existing instanceof Landmark) {
            $landmark = $existing;
        } else {
            $landmark = new Landmark(
                cityId: $cityId,
                name: $data['name'],
                slug: $data['slug'],
                nameGenitive: $data['nameGenitive'],
                latitude: $data['latitude'],
                longitude: $data['longitude'],
            );
            $this->entityManager->persist($landmark);
        }

        $landmark->setName($data['name']);
        $landmark->setNameGenitive($data['nameGenitive']);
        $landmark->setSlug($data['slug']);
        $landmark->setLatitude($data['latitude']);
        $landmark->setLongitude($data['longitude']);
        $landmark->setCategory($data['category']);
        $landmark->setShortDescription($data['shortDescription']);
        $landmark->setDescription($data['description']);
        $landmark->setAddress($data['address']);
        $landmark->setFacts($data['facts']);
        $landmark->setGuestTips($data['guestTips']);
        $landmark->setSortOrder($data['sortOrder']);
        $landmark->setIsActive(false);

        if (isset($data['imageUrl']) && $landmark->getImageUrl() === null) {
            $landmark->setImageUrl($data['imageUrl']);
        }
    }

    /**
     * @return array<string, list<array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * }>>
     */
    private static function landmarksByCitySlug(): array
    {
        return [
            'minsk' => self::minskLandmarks(),
            'brest' => self::brestLandmarks(),
            'vitebsk' => self::vitebskLandmarks(),
            'gomel' => self::gomelLandmarks(),
            'grodno' => self::grodnoLandmarks(),
            'mogilev' => self::mogilevLandmarks(),
        ];
    }

    /**
     * @return list<array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * }>
     */
    private static function minskLandmarks(): array
    {
        return [
            [
                'name' => 'Национальная библиотека',
                'nameGenitive' => 'Национальной библиотеки',
                'slug' => 'natsionalnaya-biblioteka',
                'latitude' => 53.9312,
                'longitude' => 27.6455,
                'category' => 'sight',
                'shortDescription' => 'Знаменитый «алмаз знаний» — ромбокубооктаэдр со смотровой площадкой и вечерней подсветкой.',
                'description' => '<p>Национальная библиотека Беларуси — один из самых узнаваемых символов Минска. Здание в форме ромбокубооктаэдра высотой 73 метра открыто в 2006 году.</p><p>Смотровая площадка на 23-м этаже открывает панораму города. Вечером фасад превращается в медиаэкран с подсветкой.</p>',
                'address' => 'пр. Независимости, 116, Минск',
                'facts' => [
                    ['label' => 'Год открытия', 'value' => '2006'],
                    ['label' => 'Высота', 'value' => '73 м'],
                    ['label' => 'Метро', 'value' => 'Восток'],
                ],
                'guestTips' => [
                    'Приезжайте за час до заката — увидите и дневную панораму, и подсветку.',
                    'От метро «Восток» — около 5 минут пешком.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Площадь Независимости',
                'nameGenitive' => 'Площади Независимости',
                'slug' => 'ploshchad-nezavisimosti',
                'latitude' => 53.8938,
                'longitude' => 27.5474,
                'category' => 'sight',
                'shortDescription' => 'Главная площадь Минска с Домом правительства, Красным костёлом и парадной застройкой сталинского ампира.',
                'description' => '<p>Площадь Независимости — сердце административного центра Минска. Здесь проходят парады и городские праздники.</p><p>Рядом Красный костёл, ГУМ и станция метро «Площадь Ленина».</p>',
                'address' => 'пл. Независимости, Минск',
                'facts' => [
                    ['label' => 'Площадь', 'value' => 'около 7 га'],
                    ['label' => 'Стиль', 'value' => 'сталинский ампир'],
                    ['label' => 'Метро', 'value' => 'Площадь Ленина'],
                ],
                'guestTips' => [
                    'Лучшие фото — с обзорных точек у Дома правительства.',
                    'Вечером включается подсветка фасадов.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Большой театр оперы и балета',
                'nameGenitive' => 'Большого театра оперы и балета',
                'slug' => 'teatr-opery-i-baleta',
                'latitude' => 53.9107,
                'longitude' => 27.5613,
                'category' => 'sight',
                'shortDescription' => 'Главная оперная сцена страны в историческом здании на пересечении проспекта Независимости и ул. Красноармейской.',
                'description' => '<p>Национальный академический Большой театр оперы и балета — одна из визитных карточек культурного Минска.</p><p>Рядом парк имени Янки Купалы и пешеходная улица Зыбицкая.</p>',
                'address' => 'пл. Парижской Коммуны, 1, Минск',
                'facts' => [
                    ['label' => 'Открыт', 'value' => '1933'],
                    ['label' => 'Вместимость', 'value' => 'более 1200 зрителей'],
                    ['label' => 'Метро', 'value' => 'Октябрьская'],
                ],
                'guestTips' => [
                    'Билеты на популярные спектакли лучше брать заранее.',
                    'После представления удобно прогуляться по центру.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Верхний город',
                'nameGenitive' => 'Верхнего города',
                'slug' => 'verhnij-gorod',
                'latitude' => 53.9046,
                'longitude' => 27.5567,
                'category' => 'sight',
                'shortDescription' => 'Исторический центр Минска: Ратуша, Свято-Духов кафедральный собор, узкие улочки и панорама на Свислочь.',
                'description' => '<p>Верхний город — старейшая часть Минска с архитектурой XVII–XIX веков. Здесь Ратуша, костёлы и музеи, а по выходным часто проходят ярмарки ремёсел.</p><p>Отсюда удобно дойти до Острова слёз и набережной.</p>',
                'address' => 'пл. Свободы, Минск',
                'facts' => [
                    ['label' => 'Период', 'value' => 'XVI–XIX вв.'],
                    ['label' => 'Символ', 'value' => 'Минская ратуша'],
                    ['label' => 'Метро', 'value' => 'Немига'],
                ],
                'guestTips' => [
                    'Лучше гулять пешком — парковка в центре ограничена.',
                    'Вечером включается архитектурная подсветка.',
                ],
                'sortOrder' => 40,
            ],
            [
                'name' => 'Остров Слёз',
                'nameGenitive' => 'Острова Слёз',
                'slug' => 'ostrov-slez',
                'latitude' => 53.9019,
                'longitude' => 27.5611,
                'category' => 'sight',
                'shortDescription' => 'Мемориальный комплекс в память о белорусских воинах-интернационалистах на искусственном острове у Троицкого предместья.',
                'description' => '<p>Остров Слёз — тихое место для прогулки у воды в самом центре Минска. Часовня, аллея памяти и мост к острову — популярная точка для фотографий.</p>',
                'address' => 'ул. Троицкое предместье, Минск',
                'facts' => [
                    ['label' => 'Открыт', 'value' => '1996'],
                    ['label' => 'Река', 'value' => 'Свислочь'],
                    ['label' => 'Рядом', 'value' => 'Троицкое предместье'],
                ],
                'guestTips' => [
                    'Удобно совместить с прогулкой по Верхнему городу.',
                    'На закате открывается красивый вид на воду и мост.',
                ],
                'sortOrder' => 50,
            ],
            [
                'name' => 'Парк Горького',
                'nameGenitive' => 'Парка Горького',
                'slug' => 'park-gorkogo',
                'latitude' => 53.9094,
                'longitude' => 27.5792,
                'category' => 'park',
                'shortDescription' => 'Центральный парк у Свислочи: аллеи, карусели, летние кафе и набережная в шаге от проспекта Независимости.',
                'description' => '<p>Парк культуры и отдыха имени Максима Горького — любимое место прогулок минчан и гостей города.</p><p>Летом работают аттракционы, зимой — каток и праздничные ярмарки.</p>',
                'address' => 'пр. Независимости, 91, Минск',
                'facts' => [
                    ['label' => 'Площадь', 'value' => 'около 28 га'],
                    ['label' => 'Река', 'value' => 'Свислочь'],
                    ['label' => 'Метро', 'value' => 'Площадь Якуба Коласа'],
                ],
                'guestTips' => [
                    'В выходные парк оживлённый — для спокойной прогулки приезжайте утром.',
                ],
                'sortOrder' => 60,
            ],
            [
                'name' => 'Вокзал Минск-Пассажирский',
                'nameGenitive' => 'вокзала Минск-Пассажирский',
                'slug' => 'vokzal-minsk-passazhirskiy',
                'latitude' => 53.8884,
                'longitude' => 27.5512,
                'category' => 'station',
                'shortDescription' => 'Главный железнодорожный вокзал Минска — удобная точка для краткосрочной аренды при приезде и перед отъездом.',
                'description' => '<p>Минск-Пассажирский связывает столицу с городами Беларуси и соседних стран. Рядом метро, автобусные остановки и сервисы для путешественников.</p>',
                'address' => 'пл. Привокзальная, 5, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'Метро', 'value' => 'Площадь Ленина'],
                ],
                'guestTips' => [
                    'Квартиры рядом удобны для ранних поездов и поздних прибытий.',
                ],
                'sortOrder' => 70,
            ],
            [
                'name' => 'Минск-Арена',
                'nameGenitive' => 'Минск-Арены',
                'slug' => 'minsk-arena',
                'latitude' => 53.9394,
                'longitude' => 27.4828,
                'category' => 'stadium',
                'shortDescription' => 'Многофункциональная арена для концертов, спортивных событий и шоу — крупнейший крытый стадион страны.',
                'description' => '<p>Минск-Арена принимает хоккейные матчи, концерты мировых артистов и масштабные мероприятия.</p><p>В дни событий спрос на жильё поблизости заметно растёт.</p>',
                'address' => 'пр. Победителей, 111, Минск',
                'facts' => [
                    ['label' => 'Открыта', 'value' => '2010'],
                    ['label' => 'Вместимость', 'value' => 'до 15 000 зрителей'],
                ],
                'guestTips' => [
                    'Бронируйте жильё заранее в дни крупных концертов.',
                ],
                'sortOrder' => 80,
            ],
            [
                'name' => 'Дворец спорта',
                'nameGenitive' => 'Дворца спорта',
                'slug' => 'dvorets-sporta',
                'latitude' => 53.9172,
                'longitude' => 27.5486,
                'category' => 'stadium',
                'shortDescription' => 'Концертно-спортивный комплекс в центре Минска — хоккей, концерты и массовые мероприятия.',
                'description' => '<p>Минский дворец спорта — одна из главных площадок столицы для спортивных соревнований и концертов. Расположен рядом с проспектом Победителей и Немигой.</p>',
                'address' => 'пр. Победителей, 4, Минск',
                'facts' => [
                    ['label' => 'Открыт', 'value' => '1966'],
                    ['label' => 'Вместимость', 'value' => 'около 15 000 зрителей'],
                    ['label' => 'Метро', 'value' => 'Немига'],
                ],
                'guestTips' => [
                    'В дни матчей и концертов закладывайте время на дорогу к площадке.',
                ],
                'sortOrder' => 90,
            ],
        ];
    }

    /**
     * @return list<array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * }>
     */
    private static function brestLandmarks(): array
    {
        return [
            [
                'name' => 'Брестская крепость',
                'nameGenitive' => 'Брестской крепости',
                'slug' => 'brestskaya-krepost',
                'latitude' => 52.0827,
                'longitude' => 23.6542,
                'category' => 'sight',
                'shortDescription' => 'Мемориальный комплекс-музей «Брестская крепость-герой» — главный символ мужества и истории города.',
                'description' => '<p>Брестская крепость — одно из самых посещаемых мест Беларуси. Здесь музей, монумент «Мужество» и экспозиции о героической обороне 1941 года.</p><p>На территории удобно гулять несколько часов.</p>',
                'address' => 'ул. 28 Июля, 62, Брест',
                'facts' => [
                    ['label' => 'Основана', 'value' => '1836'],
                    ['label' => 'Статус', 'value' => 'музей-заповедник'],
                    ['label' => 'Река', 'value' => 'Западный Буг и Мухавец'],
                ],
                'guestTips' => [
                    'Выделите на посещение минимум полдня.',
                    'Летом берите воду и головной убор — много открытых пространств.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Вокзал Брест',
                'nameGenitive' => 'вокзала Брест',
                'slug' => 'vokzal-brest',
                'latitude' => 52.1013,
                'longitude' => 23.6558,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал на пограничном направлении — удобная точка для транзитных гостей и поездок в Европу.',
                'description' => '<p>Брестский вокзал — важный транспортный узел на западе страны. Рядом остановки общественного транспорта и сервисы для путешественников.</p>',
                'address' => 'пл. Ленина, 3, Брест',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'Направления', 'value' => 'Минск, Польша'],
                ],
                'guestTips' => [
                    'Удобно снимать жильё рядом при ночных и ранних поездах.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Площадь Ленина',
                'nameGenitive' => 'Площади Ленина',
                'slug' => 'ploshchad-lenina',
                'latitude' => 52.0976,
                'longitude' => 23.7341,
                'category' => 'sight',
                'shortDescription' => 'Центральная площадь Бреста с собором святых Симеона и Елены и парадной городской застройкой.',
                'description' => '<p>Площадь Ленина — сердце современного Бреста. Здесь проходят городские мероприятия, рядом кафе, магазины и прогулочные маршруты к набережной Мухавца.</p>',
                'address' => 'пл. Ленина, Брест',
                'facts' => [
                    ['label' => 'Символ', 'value' => 'Собор Симеона и Елены'],
                    ['label' => 'Рядом', 'value' => 'набережная Мухавца'],
                ],
                'guestTips' => [
                    'Вечером включается подсветка собора.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Музей «Берестье»',
                'nameGenitive' => 'музея «Берестье»',
                'slug' => 'muzey-berestie',
                'latitude' => 52.0889,
                'longitude' => 23.6533,
                'category' => 'sight',
                'shortDescription' => 'Музей истории древнего Бреста на месте раскопок средневекового городища XIII века.',
                'description' => '<p>Музей «Берестье» показывает остатки деревянных построек древнего Бреста, найденные археологами. Экспозиция рядом с крепостью — логичное продолжение исторического маршрута.</p>',
                'address' => 'ул. Карбышева, 52, Брест',
                'facts' => [
                    ['label' => 'Период', 'value' => 'XIII век'],
                    ['label' => 'Тип', 'value' => 'археологический музей'],
                ],
                'guestTips' => [
                    'Удобно посетить в один день с Брестской крепостью.',
                ],
                'sortOrder' => 40,
            ],
            [
                'name' => 'Свято-Николаевский собор',
                'nameGenitive' => 'Свято-Николаевского собора',
                'slug' => 'sobor-svyatogo-nikolaya',
                'latitude' => 52.0898,
                'longitude' => 23.6847,
                'category' => 'sight',
                'shortDescription' => 'Белокаменный православный собор XIX века — одна из главных архитектурных доминант центра Бреста.',
                'description' => '<p>Свято-Николаевский гарнизонный собор возведён в 1870-х годах. Высокие купола хорошо видны из разных точек города и особенно эффектны на фоне заката.</p>',
                'address' => 'ул. Советская, 2, Брест',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1876'],
                    ['label' => 'Стиль', 'value' => 'византийский'],
                ],
                'guestTips' => [
                    'Рядом пешеходная ул. Советская с кафе и сувенирными лавками.',
                ],
                'sortOrder' => 50,
            ],
        ];
    }

    /**
     * @return list<array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * }>
     */
    private static function vitebskLandmarks(): array
    {
        return [
            [
                'name' => 'Витебская ратуша',
                'nameGenitive' => 'Витебской ратуши',
                'slug' => 'vitebskaya-ratusha',
                'latitude' => 55.1958,
                'longitude' => 30.2036,
                'category' => 'sight',
                'shortDescription' => 'Белокаменная ратуша на Ратушной площади — символ Витебска и площадка городских праздников.',
                'description' => '<p>Витебская ратуша в стиле неоготики — главная архитектурная доминанта исторического центра. Рядом фонтаны, кафе и прогулочные улицы старого города.</p>',
                'address' => 'пл. Ратушная, 1, Витебск',
                'facts' => [
                    ['label' => 'Построена', 'value' => '1775'],
                    ['label' => 'Стиль', 'value' => 'неоготика'],
                ],
                'guestTips' => [
                    'Летом на площади часто проходят концерты и фестивали.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Дом-музей Марка Шагала',
                'nameGenitive' => 'Дома-музея Марка Шагала',
                'slug' => 'dom-muzey-marka-shagala',
                'latitude' => 55.1889,
                'longitude' => 30.2025,
                'category' => 'sight',
                'shortDescription' => 'Музей в доме, где родился Марк Шагал — точка для знакомства с жизнью художника и историей Покровской улицы.',
                'description' => '<p>Дом-музей Марка Шагала сохраняет атмосферу витебского детства художника. Экспозиция небольшая, но она хорошо дополняет прогулку по историческому центру.</p>',
                'address' => 'ул. Покровская, 11, Витебск',
                'facts' => [
                    ['label' => 'Год рождения Шагала', 'value' => '1887'],
                    ['label' => 'Тип', 'value' => 'литературно-мемориальный музей'],
                ],
                'guestTips' => [
                    'Совместите визит с прогулкой по Покровской улице.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Вокзал Витебск',
                'nameGenitive' => 'вокзала Витебск',
                'slug' => 'vokzal-vitebsk',
                'latitude' => 55.1845,
                'longitude' => 30.1128,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал на северном направлении — удобен для гостей, приезжающих в город на поезде.',
                'description' => '<p>Витебский вокзал обслуживает сообщение с Минском, Полоцком, Россией и Латвией. Рядом остановки автобусов и такси до центра.</p>',
                'address' => 'пл. Привокзальная, 1, Витебск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Для позднего прибытия удобно заранее выбрать жильё неподалёку.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Усадьба Суцких',
                'nameGenitive' => 'Усадьбы Суцких',
                'slug' => 'usadba-sutskikh',
                'latitude' => 55.1933,
                'longitude' => 30.2078,
                'category' => 'sight',
                'shortDescription' => 'Дворец в центре Витебска с парком — памятник архитектуры классицизма и культурная площадка города.',
                'description' => '<p>Усадьба Суцких — один из красивейших дворцовых ансамблей Витебска. В здании размещаются выставки и мероприятия, во дворе приятно прогуляться.</p>',
                'address' => 'ул. Фрунзе, 15, Витебск',
                'facts' => [
                    ['label' => 'Построена', 'value' => 'начало XIX века'],
                    ['label' => 'Стиль', 'value' => 'классицизм'],
                ],
                'guestTips' => [
                    'Проверьте афишу выставок перед визитом.',
                ],
                'sortOrder' => 40,
            ],
            [
                'name' => 'Площадь Победы',
                'nameGenitive' => 'Площади Победы',
                'slug' => 'ploshchad-pobedy',
                'latitude' => 55.1968,
                'longitude' => 30.2203,
                'category' => 'sight',
                'shortDescription' => 'Центральная городская площадь с мемориалом и парадной застройкой — оживлённый район Витебска.',
                'description' => '<p>Площадь Победы — одна из главных площадей Витебска. Здесь проходят городские мероприятия, рядом торговые улицы и остановки общественного транспорта.</p>',
                'address' => 'пл. Победы, Витебск',
                'facts' => [
                    ['label' => 'Рядом', 'value' => 'пр. Фрунзе'],
                    ['label' => 'Транспорт', 'value' => 'автобусные маршруты в центр'],
                ],
                'guestTips' => [
                    'Отсюда удобно добраться до Ратушной площади на автобусе или такси.',
                ],
                'sortOrder' => 50,
            ],
        ];
    }

    /**
     * @return list<array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * }>
     */
    private static function gomelLandmarks(): array
    {
        return [
            [
                'name' => 'Дворец Румянцевых и Паскевичей',
                'nameGenitive' => 'Дворца Румянцевых и Паскевичей',
                'slug' => 'dvorets-rumyantsevyh-i-paskevichey',
                'latitude' => 52.4242,
                'longitude' => 31.0145,
                'category' => 'sight',
                'shortDescription' => 'Дворцово-парковый ансамбль на высоком берегу Сожа — главная достопримечательность Гомеля.',
                'description' => '<p>Дворец Румянцевых и Паскевичей окружён парком с прудами, часовней-усыпальницей и смотровыми площадками над рекой Сож. Это must-see для гостей города.</p>',
                'address' => 'пл. Ленина, 4, Гомель',
                'facts' => [
                    ['label' => 'Основан', 'value' => 'конец XVIII века'],
                    ['label' => 'Парк', 'value' => 'около 40 га'],
                    ['label' => 'Река', 'value' => 'Сож'],
                ],
                'guestTips' => [
                    'На прогулку по парку заложите 1–2 часа.',
                    'Лучшие виды на Сож — с обзорных площадок у дворца.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Вокзал Гомель',
                'nameGenitive' => 'вокзала Гомель',
                'slug' => 'vokzal-gomel',
                'latitude' => 52.4316,
                'longitude' => 30.9989,
                'category' => 'station',
                'shortDescription' => 'Главный железнодорожный вокзал Гомеля на южном направлении страны.',
                'description' => '<p>Гомельский вокзал — транспортные ворота города. Удобен для гостей, которые приезжают на поезде из Минска, Брянска и других городов.</p>',
                'address' => 'пл. Привокзальная, 1, Гомель',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Квартиры рядом удобны при раннем отъезде.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Собор святых Петра и Павла',
                'nameGenitive' => 'Собора святых Петра и Павла',
                'slug' => 'sobor-petra-i-pavla',
                'latitude' => 52.4238,
                'longitude' => 31.0165,
                'category' => 'sight',
                'shortDescription' => 'Католический собор в парке возле дворца — одна из старейших построек Гомеля.',
                'description' => '<p>Собор святых Петра и Павла возведён в начале XIX века. Он входит в ансамбль дворцового парка и хорошо смотрится на фоне зелени и панорамы Сожа.</p>',
                'address' => 'пл. Ленина, 6, Гомель',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1809–1819'],
                    ['label' => 'Стиль', 'value' => 'классицизм'],
                ],
                'guestTips' => [
                    'Удобно посетить вместе с дворцово-парковым ансамблем.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Площадь Ленина',
                'nameGenitive' => 'Площади Ленина',
                'slug' => 'ploshchad-lenina',
                'latitude' => 52.4310,
                'longitude' => 31.0065,
                'category' => 'sight',
                'shortDescription' => 'Главная площадь Гомеля с драмтеатром, магистратурой и парадной городской застройкой.',
                'description' => '<p>Площадь Ленина — центр общественной жизни Гомеля. Отсюда удобно дойти до дворцового парка, набережной и основных торговых улиц.</p>',
                'address' => 'пл. Ленина, Гомель',
                'facts' => [
                    ['label' => 'Рядом', 'value' => 'драматический театр'],
                    ['label' => 'Река', 'value' => 'Сож'],
                ],
                'guestTips' => [
                    'Вечером включается подсветка фасадов в центре.',
                ],
                'sortOrder' => 40,
            ],
            [
                'name' => 'Набережная реки Сож',
                'nameGenitive' => 'набережной реки Сож',
                'slug' => 'naberezhnaya-sozh',
                'latitude' => 52.4225,
                'longitude' => 31.0120,
                'category' => 'park',
                'shortDescription' => 'Прогулочная набережная с видом на Сож, мосты и парк — популярное место отдыха гомельчан.',
                'description' => '<p>Набережная Сожа тянется вдоль центральной части города. Здесь приятно гулять вечером, фотографировать мосты и спускаться к воде из парка у дворца.</p>',
                'address' => 'наб. Сожа, Гомель',
                'facts' => [
                    ['label' => 'Река', 'value' => 'Сож'],
                    ['label' => 'Сезон', 'value' => 'особенно популярна весной и летом'],
                ],
                'guestTips' => [
                    'Совместите прогулку с посещением дворца и собора.',
                ],
                'sortOrder' => 50,
            ],
        ];
    }

    /**
     * @return list<array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * }>
     */
    private static function grodnoLandmarks(): array
    {
        return [
            [
                'name' => 'Старый замок',
                'nameGenitive' => 'Старого замка',
                'slug' => 'staryj-zamok',
                'latitude' => 53.6775,
                'longitude' => 23.8233,
                'category' => 'sight',
                'shortDescription' => 'Средневековый королевский замок на высоком берегу Немана — символ исторического Гродно.',
                'description' => '<p>Старый замок в Гродно — один из старейших сохранившихся замков Беларуси. Сейчас здесь музей, а с валов открывается вид на Неман и город.</p>',
                'address' => 'ул. Замковая, 22, Гродно',
                'facts' => [
                    ['label' => 'Период', 'value' => 'XI–XVIII вв.'],
                    ['label' => 'Река', 'value' => 'Неман'],
                ],
                'guestTips' => [
                    'Поднимитесь на вал для панорамных фотографий.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Новый замок',
                'nameGenitive' => 'Нового замка',
                'slug' => 'novyj-zamok',
                'latitude' => 53.6789,
                'longitude' => 23.8253,
                'category' => 'sight',
                'shortDescription' => 'Барочный королевский дворец XVIII века напротив Старого замка — жемчужина архитектуры Гродно.',
                'description' => '<p>Новый замок построен для короля Августа III. Здание восстановлено и используется как музей, рядом — исторический центр и пешеходные улицы.</p>',
                'address' => 'ул. Замковая, 25, Гродно',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1737–1751'],
                    ['label' => 'Стиль', 'value' => 'барокко'],
                ],
                'guestTips' => [
                    'Удобно осматривать вместе со Старым замком за один визит.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Коложская церковь',
                'nameGenitive' => 'Коложской церкви',
                'slug' => 'kolozhskaya-tserkov',
                'latitude' => 53.6830,
                'longitude' => 23.8192,
                'category' => 'sight',
                'shortDescription' => 'Уникальный памятник XII века на крутом берегу Немана — одна из старейших церквей Беларуси.',
                'description' => '<p>Борисоглебская (Коложская) церковь — редкий образец чернорусского зодчества. Из-за оползня часть древних стен ушла к реке, но храм остаётся действующим и включён в список ЮНЕСКО.</p>',
                'address' => 'ул. Колхозная, 76, Гродно',
                'facts' => [
                    ['label' => 'Построена', 'value' => 'около 1180 года'],
                    ['label' => 'Статус', 'value' => 'памятник ЮНЕСКО'],
                ],
                'guestTips' => [
                    'Лучше посещать днём — подъезд и обзорные точки удобнее при свете.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Гродно',
                'nameGenitive' => 'вокзала Гродно',
                'slug' => 'vokzal-grodno',
                'latitude' => 53.6770,
                'longitude' => 23.8357,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал Гродно — удобная точка для гостей, приезжающих из Минска и Литвы.',
                'description' => '<p>Гродненский вокзал расположен недалеко от центра. Отсюда удобно добраться до замков, Соборной улицы и набережной Немана.</p>',
                'address' => 'ул. Вокзальная, 3, Гродно',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут пешком'],
                ],
                'guestTips' => [
                    'Для короткой поездки удобно выбрать жильё в центре рядом с вокзалом.',
                ],
                'sortOrder' => 40,
            ],
            [
                'name' => 'Соборный костёл',
                'nameGenitive' => 'Соборного костёла',
                'slug' => 'sobornyj-kostyol',
                'latitude' => 53.6775,
                'longitude' => 23.8290,
                'category' => 'sight',
                'shortDescription' => 'Фарный костёл Успения Пресвятой Девы Марии — величественный храм на Соборной улице в сердце Гродно.',
                'description' => '<p>Соборный костёл — один из крупнейших католических храмов Беларуси. Высокий фасад и интерьер в стиле виленского барокко привлекают туристов и паломников.</p>',
                'address' => 'ул. Социалистическая, 1, Гродно',
                'facts' => [
                    ['label' => 'Освящён', 'value' => '1683'],
                    ['label' => 'Стиль', 'value' => 'виленское барокко'],
                ],
                'guestTips' => [
                    'Рядом пешеходная Соборная улица с кафе и сувенирами.',
                ],
                'sortOrder' => 50,
            ],
        ];
    }

    /**
     * @return list<array{
     *   name: string,
     *   nameGenitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   shortDescription: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guestTips: list<string>,
     *   sortOrder: int,
     *   imageUrl?: string
     * }>
     */
    private static function mogilevLandmarks(): array
    {
        return [
            [
                'name' => 'Могилёвская ратуша',
                'nameGenitive' => 'Могилёвской ратуши',
                'slug' => 'mogilevskaya-ratusha',
                'latitude' => 53.8944,
                'longitude' => 30.3349,
                'category' => 'sight',
                'shortDescription' => 'Белокаменная ратуша на Площади Звёзд — архитектурный символ исторического центра Могилёва.',
                'description' => '<p>Могилёвская ратуша в стиле магдебургского барокко украшает Площадь Звёзд. Здание узнаваемо и хорошо смотрится на фоне городских праздников и вечерней подсветки.</p>',
                'address' => 'пл. Звёзд, 1, Могилёв',
                'facts' => [
                    ['label' => 'Построена', 'value' => '1698–1704'],
                    ['label' => 'Стиль', 'value' => 'магдебургское барокко'],
                ],
                'guestTips' => [
                    'Лучшие фото — с фонтанов на площади.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Площадь Звёзд',
                'nameGenitive' => 'Площади Звёзд',
                'slug' => 'ploshchad-zvezd',
                'latitude' => 53.8947,
                'longitude' => 30.3346,
                'category' => 'sight',
                'shortDescription' => 'Главная площадь Могилёва с ратушей, фонтанами и парадной застройкой — центр городских событий.',
                'description' => '<p>Площадь Звёзд — сердце исторического Могилёва. Здесь проходят праздники, концерты и городские ярмарки, рядом кафе и прогулочные улицы.</p>',
                'address' => 'пл. Звёзд, Могилёв',
                'facts' => [
                    ['label' => 'Символ', 'value' => 'Могилёвская ратуша'],
                    ['label' => 'Река', 'value' => 'Днепр'],
                ],
                'guestTips' => [
                    'Вечером включается подсветка площади и ратуши.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Вокзал Могилёв',
                'nameGenitive' => 'вокзала Могилёв',
                'slug' => 'vokzal-mogilev',
                'latitude' => 53.8895,
                'longitude' => 30.3304,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал Могилёва — удобная точка для гостей, приезжающих на поезде.',
                'description' => '<p>Могилёвский вокзал обслуживает сообщение с Минском, Оршей, Бобруйском и другими городами. До центра и Площади Звёзд — короткая поездка на такси или автобусе.</p>',
                'address' => 'пл. Привокзальная, 1, Могилёв',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Для раннего поезда удобно остановиться рядом с вокзалом.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Могилёвский драматический театр',
                'nameGenitive' => 'Могилёвского драматического театра',
                'slug' => 'dramaticheskiy-teatr',
                'latitude' => 53.8968,
                'longitude' => 30.3312,
                'category' => 'sight',
                'shortDescription' => 'Главный театр города в историческом здании — культурная точка притяжения в центре Могилёва.',
                'description' => '<p>Могилёвский областной драматический театр расположен в центре города. Рядом площади, набережная Днепра и прогулочные маршруты по исторической части.</p>',
                'address' => 'пл. Славы, 2, Могилёв',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'драматический театр'],
                    ['label' => 'Рядом', 'value' => 'Площадь Звёзд'],
                ],
                'guestTips' => [
                    'Билеты на премьеры лучше покупать заранее.',
                ],
                'sortOrder' => 40,
            ],
            [
                'name' => 'Свято-Николаевский монастырь',
                'nameGenitive' => 'Свято-Николаевского монастыря',
                'slug' => 'nikolaevskiy-monastyr',
                'latitude' => 53.9008,
                'longitude' => 30.3395,
                'category' => 'sight',
                'shortDescription' => 'Бернардинский монастырский комплекс XVII века — один из старейших архитектурных ансамблей Могилёва.',
                'description' => '<p>Свято-Николаевский (Бернардинский) монастырь включает костёл и жилые корпуса в стиле барокко. Комплекс расположен на возвышенности с видом на Днепр.</p>',
                'address' => 'ул. Никольская, 12, Могилёв',
                'facts' => [
                    ['label' => 'Основан', 'value' => '1630-е годы'],
                    ['label' => 'Стиль', 'value' => 'барокко'],
                ],
                'guestTips' => [
                    'Удобно совместить с прогулкой по набережной Днепра.',
                ],
                'sortOrder' => 50,
            ],
        ];
    }
}
