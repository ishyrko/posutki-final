<?php

declare(strict_types=1);

namespace App\Infrastructure\Symfony\Command;

use App\Application\Service\LandmarkContentPersistNormalizer;
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
    description: 'Upsert demo landmarks for catalog apartment cities (inactive by default)',
)]
final class SeedDemoLandmarksCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly LandmarkRepositoryInterface $landmarkRepository,
        private readonly LandmarkContentPersistNormalizer $landmarkContentPersistNormalizer,
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

        $cityCount = count(self::landmarksByCitySlug()) - count($skippedCities);
        $io->success(sprintf(
            'Достопримечательности обновлены: %d шт. в %d городах (выключены). Включите нужные в админке и запустите make sync-landmark-proximity.',
            $total,
            $cityCount,
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

        $this->landmarkContentPersistNormalizer->normalize($landmark);
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
            'baranovichi' => self::baranovichiLandmarks(),
            'pinsk' => self::pinskLandmarks(),
            'bobruysk' => self::bobruyskLandmarks(),
            'molodechno' => self::molodechnoLandmarks(),
            'logoysk' => self::logoyskLandmarks(),
            'orsha' => self::orshaLandmarks(),
            'novopolotsk' => self::novopolotskLandmarks(),
            'svetlogorsk' => self::svetlogorskLandmarks(),
            'smorgon' => self::smorgonLandmarks(),
            'zhlobin' => self::zhlobinLandmarks(),
            'volkovysk' => self::volkovyskLandmarks(),
            'novolukoml' => self::novolukomlLandmarks(),
            'krichev' => self::krichevLandmarks(),
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
    private static function baranovichiLandmarks(): array
    {
        return [
            [
                'name' => 'Собор Покрова Пресвятой Богородицы',
                'nameGenitive' => 'Собора Покрова Пресвятой Богородицы',
                'slug' => 'pokrovskiy-sobor',
                'latitude' => 53.1322,
                'longitude' => 26.0040,
                'category' => 'sight',
                'shortDescription' => 'Белокаменный православный собор в стиле неоклассицизма — архитектурная доминанта центра Барановичей.',
                'description' => '<p>Собор Покрова Пресвятой Богородицы построен в 1920–1930-х годах. Высокий купол хорошо виден из разных точек города и особенно эффектно смотрится вечером при подсветке.</p>',
                'address' => 'ул. Куйбышева, 9а, Барановичи',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1924–1931'],
                    ['label' => 'Стиль', 'value' => 'неоклассицизм'],
                ],
                'guestTips' => [
                    'Рядом центральные улицы с кафе и магазинами.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Костёл Воздвижения Святого Креста',
                'nameGenitive' => 'Костёла Воздвижения Святого Креста',
                'slug' => 'kostyol-vozdvizheniya',
                'latitude' => 53.1328,
                'longitude' => 26.0165,
                'category' => 'sight',
                'shortDescription' => 'Краснокирпичный католический храм начала XX века — один из символов Барановичей.',
                'description' => '<p>Костёл Воздвижения Святого Креста — действующий католический храм в центре города. Кирпичная неоготика и высокая башня делают его популярной точкой для фотографий.</p>',
                'address' => 'ул. Куйбышева, 30, Барановичи',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1903–1924'],
                    ['label' => 'Стиль', 'value' => 'неоготика'],
                ],
                'guestTips' => [
                    'Удобно совместить осмотр с прогулкой к Покровскому собору.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Вокзал Барановичи-Центральные',
                'nameGenitive' => 'вокзала Барановичи-Центральные',
                'slug' => 'vokzal-baranovichi',
                'latitude' => 53.1315,
                'longitude' => 26.0150,
                'category' => 'station',
                'shortDescription' => 'Крупный железнодорожный узел на западе Беларуси — удобная точка для транзитных гостей.',
                'description' => '<p>Вокзал Барановичи-Центральные связывает город с Минском, Брестом и другими направлениями. До центра и главных храмов — короткая поездка на такси или пешком.</p>',
                'address' => 'пл. Ленина, 1, Барановичи',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут пешком'],
                ],
                'guestTips' => [
                    'Для ранних поездов удобно снимать жильё рядом с вокзалом.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Краеведческий музей',
                'nameGenitive' => 'краеведческого музея',
                'slug' => 'kraevedcheskiy-muzey',
                'latitude' => 53.1335,
                'longitude' => 26.0188,
                'category' => 'sight',
                'shortDescription' => 'Музей истории Барановичей и района — короткое знакомство с прошлым города у железнодорожного узла.',
                'description' => '<p>Барановичский краеведческий музей рассказывает о возникновении города, железной дороге и местных традициях. Удобная остановка в маршруте по центру.</p>',
                'address' => 'ул. Советская, 72, Барановичи',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'краеведческий музей'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Проверьте режим работы перед визитом.',
                ],
                'sortOrder' => 40,
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
    private static function pinskLandmarks(): array
    {
        return [
            [
                'name' => 'Иезуитский коллегиум',
                'nameGenitive' => 'Иезуитского коллегиума',
                'slug' => 'iezuitskiy-kollegium',
                'latitude' => 52.1128,
                'longitude' => 26.1025,
                'category' => 'sight',
                'shortDescription' => 'Монументальный барочный комплекс XVII века на берегу Пины — главная достопримечательность Пинска.',
                'description' => '<p>Пинский иезуитский коллегиум — один из крупнейших памятников барокко в Беларуси. Сейчас в здании музей, а рядом — исторический центр и набережная.</p>',
                'address' => 'ул. Ленина, 23, Пинск',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1631–1675'],
                    ['label' => 'Стиль', 'value' => 'барокко'],
                ],
                'guestTips' => [
                    'Лучшие фото — с набережной Пины.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Собор Святой Варвары',
                'nameGenitive' => 'Собора Святой Варвары',
                'slug' => 'sobor-svyatoy-varvary',
                'latitude' => 52.1184,
                'longitude' => 26.1105,
                'category' => 'sight',
                'shortDescription' => 'Бывший бернардинский костёл XVIII века — белокаменный храм в центре Пинска.',
                'description' => '<p>Свято-Варваринский собор входит в ансамбль бывшего монастыря бернардинцев. Фасад позднего барокко и колокольня выделяются на фоне городской застройки.</p>',
                'address' => 'ул. Советская, 34, Пинск',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1786–1787'],
                    ['label' => 'Стиль', 'value' => 'позднее барокко'],
                ],
                'guestTips' => [
                    'Удобно посетить вместе с коллегиумом за одну прогулку.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Дворец Бутримовича',
                'nameGenitive' => 'Дворца Бутримовича',
                'slug' => 'dvorets-butrimovicha',
                'latitude' => 52.1119,
                'longitude' => 26.1020,
                'category' => 'sight',
                'shortDescription' => 'Классицистический дворец XVIII века у реки — жемчужина исторического Пинска.',
                'description' => '<p>Дворец Бутримовича стоит у слияния Пины и Припяти. Здание в стиле классицизма окружено тихим сквером и хорошо смотрится с воды.</p>',
                'address' => 'ул. Ленина, 37, Пинск',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1794'],
                    ['label' => 'Стиль', 'value' => 'классицизм'],
                ],
                'guestTips' => [
                    'Совместите визит с прогулкой по набережной.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Набережная реки Пины',
                'nameGenitive' => 'набережной реки Пины',
                'slug' => 'naberezhnaya-piny',
                'latitude' => 52.1120,
                'longitude' => 26.1005,
                'category' => 'park',
                'shortDescription' => 'Прогулочная набережная у исторического центра — популярное место отдыха пинчан и гостей.',
                'description' => '<p>Набережная Пины тянется вдоль коллегиума и дворца. Здесь приятно гулять вечером, фотографировать фасады и смотреть на речные пейзажи Полесья.</p>',
                'address' => 'наб. Пины, Пинск',
                'facts' => [
                    ['label' => 'Река', 'value' => 'Пина'],
                    ['label' => 'Рядом', 'value' => 'Иезуитский коллегиум'],
                ],
                'guestTips' => [
                    'На закате особенно красивые виды на воду и барокко.',
                ],
                'sortOrder' => 40,
            ],
            [
                'name' => 'Вокзал Пинск',
                'nameGenitive' => 'вокзала Пинск',
                'slug' => 'vokzal-pinsk',
                'latitude' => 52.1210,
                'longitude' => 26.0730,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал Пинска — удобная точка для гостей, приезжающих в полесский город на поезде.',
                'description' => '<p>Пинский вокзал обслуживает сообщение с Брестом и другими городами. До исторического центра — короткая поездка на такси или автобусе.</p>',
                'address' => 'ул. Брестская, 11, Пинск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Для позднего прибытия удобно заранее выбрать жильё неподалёку.',
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
    private static function bobruyskLandmarks(): array
    {
        return [
            [
                'name' => 'Бобруйская крепость',
                'nameGenitive' => 'Бобруйской крепости',
                'slug' => 'bobruyskaya-krepost',
                'latitude' => 53.1397,
                'longitude' => 29.2214,
                'category' => 'sight',
                'shortDescription' => 'Земляная крепость XIX века на берегу Березины — главная историческая достопримечательность Бобруйска.',
                'description' => '<p>Бобруйская крепость заложена в 1810 году по указу Александра I. Сохранившиеся валы, казематы и панорама реки делают её must-see для гостей города.</p>',
                'address' => 'ул. Социалистическая, Бобруйск',
                'facts' => [
                    ['label' => 'Основана', 'value' => '1810'],
                    ['label' => 'Река', 'value' => 'Березина'],
                ],
                'guestTips' => [
                    'Выделите на прогулку по валам минимум час.',
                    'Удобная обувь — много неровных троп.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Драматический театр имени В. И. Дунина-Марцинкевича',
                'nameGenitive' => 'драматического театра имени В. И. Дунина-Марцинкевича',
                'slug' => 'dramaticheskiy-teatr',
                'latitude' => 53.1450,
                'longitude' => 29.2250,
                'category' => 'sight',
                'shortDescription' => 'Главная театральная сцена Бобруйска в центре города — культурная точка притяжения.',
                'description' => '<p>Бобруйский драматический театр расположен в исторической части города. Рядом площади, кафе и прогулочные маршруты к крепости и Березине.</p>',
                'address' => 'ул. Социалистическая, 85, Бобруйск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'драматический театр'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Билеты на популярные спектакли лучше брать заранее.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Площадь Ленина',
                'nameGenitive' => 'Площади Ленина',
                'slug' => 'ploshchad-lenina',
                'latitude' => 53.1478,
                'longitude' => 29.2215,
                'category' => 'sight',
                'shortDescription' => 'Центральная площадь Бобруйска с парадной застройкой — оживлённый район города.',
                'description' => '<p>Площадь Ленина — сердце современного Бобруйска. Здесь проходят городские мероприятия, рядом торговые улицы и остановки общественного транспорта.</p>',
                'address' => 'пл. Ленина, Бобруйск',
                'facts' => [
                    ['label' => 'Рядом', 'value' => 'театр и крепость'],
                    ['label' => 'Транспорт', 'value' => 'автобусные маршруты'],
                ],
                'guestTips' => [
                    'Отсюда удобно начать маршрут к крепости.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Бобруйск',
                'nameGenitive' => 'вокзала Бобруйск',
                'slug' => 'vokzal-bobruysk',
                'latitude' => 53.1390,
                'longitude' => 29.2060,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал на направлении Минск — Гомель — удобен для гостей, приезжающих на поезде.',
                'description' => '<p>Бобруйский вокзал — транспортные ворота города. До центра и крепости — короткая поездка на такси или автобусе.</p>',
                'address' => 'пл. Привокзальная, 1, Бобруйск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Квартиры рядом удобны при раннем отъезде.',
                ],
                'sortOrder' => 40,
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
    private static function molodechnoLandmarks(): array
    {
        return [
            [
                'name' => 'Костёл Святого Казимира',
                'nameGenitive' => 'Костёла Святого Казимира',
                'slug' => 'kostyol-svyatogo-kazimira',
                'latitude' => 54.3070,
                'longitude' => 26.8385,
                'category' => 'sight',
                'shortDescription' => 'Краснокирпичный католический храм начала XX века — архитектурный символ Молодечно.',
                'description' => '<p>Костёл Святого Казимира — одна из главных достопримечательностей города. Неоготический фасад и башня хорошо смотрятся на фоне центральной застройки.</p>',
                'address' => 'ул. Великий Гостинец, 61, Молодечно',
                'facts' => [
                    ['label' => 'Построен', 'value' => '1902–1910'],
                    ['label' => 'Стиль', 'value' => 'неоготика'],
                ],
                'guestTips' => [
                    'Вечером включается подсветка фасада.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Церковь Покрова Пресвятой Богородицы',
                'nameGenitive' => 'Церкви Покрова Пресвятой Богородицы',
                'slug' => 'pokrovskaya-tserkov',
                'latitude' => 54.3085,
                'longitude' => 26.8455,
                'category' => 'sight',
                'shortDescription' => 'Православный храм в центре Молодечно — спокойная точка для прогулки по исторической части.',
                'description' => '<p>Покровская церковь — действующий православный храм города. Рядом удобные маршруты к костёлу, площади и вокзалу.</p>',
                'address' => 'ул. Я. Купалы, Молодечно',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Удобно совместить с осмотром костёла Святого Казимира.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Площадь Центральная',
                'nameGenitive' => 'Центральной площади',
                'slug' => 'ploshchad-tsentralnaya',
                'latitude' => 54.3100,
                'longitude' => 26.8410,
                'category' => 'sight',
                'shortDescription' => 'Главная площадь Молодечно с парадной застройкой — оживлённый район города.',
                'description' => '<p>Центральная площадь — место городских праздников и ярмарок. Отсюда удобно дойти до храмов, парка и торговых улиц.</p>',
                'address' => 'пл. Центральная, Молодечно',
                'facts' => [
                    ['label' => 'Рядом', 'value' => 'костёл и парк'],
                    ['label' => 'Транспорт', 'value' => 'автобусные маршруты'],
                ],
                'guestTips' => [
                    'В выходные площадь оживлённая — для спокойной прогулки приезжайте утром.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Молодечно',
                'nameGenitive' => 'вокзала Молодечно',
                'slug' => 'vokzal-molodechno',
                'latitude' => 54.3075,
                'longitude' => 26.8440,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал на направлении Минск — Вильнюс — удобен для гостей, приезжающих на поезде.',
                'description' => '<p>Молодечненский вокзал расположен близко к центру. Отсюда удобно добраться до костёла, площади и основных улиц города.</p>',
                'address' => 'пл. Привокзальная, 1, Молодечно',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут пешком'],
                ],
                'guestTips' => [
                    'Для короткой поездки удобно выбрать жильё рядом с вокзалом.',
                ],
                'sortOrder' => 40,
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
    private static function logoyskLandmarks(): array
    {
        return [
            [
                'name' => 'Горнолыжный комплекс «Логойск»',
                'nameGenitive' => 'горнолыжного комплекса «Логойск»',
                'slug' => 'gornolyzhnyy-kompleks-logoysk',
                'latitude' => 54.2067,
                'longitude' => 27.8267,
                'category' => 'sight',
                'shortDescription' => 'Популярный горнолыжный и всесезонный курорт у Логойска — трассы, прокат и отдых за городом.',
                'description' => '<p>Горнолыжный комплекс «Логойск» — главная точка притяжения региона. Зимой здесь катаются на лыжах и сноуборде, летом работают прогулочные маршруты и активный отдых.</p>',
                'address' => 'аг. Гайна, Логойский район',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'горнолыжный курорт'],
                    ['label' => 'Сезон', 'value' => 'зима — пик спроса'],
                ],
                'guestTips' => [
                    'В выходные и праздники бронируйте жильё заранее.',
                    'До курорта удобнее добираться на машине.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Усадьба Тышкевичей',
                'nameGenitive' => 'усадьбы Тышкевичей',
                'slug' => 'usadba-tyshkevichey',
                'latitude' => 54.2015,
                'longitude' => 27.8505,
                'category' => 'sight',
                'shortDescription' => 'Остатки дворцово-паркового ансамбля графов Тышкевичей — историческое сердце Логойска.',
                'description' => '<p>Усадьба Тышкевичей когда-то была одной из красивейших в Минской губернии. Сохранившиеся фрагменты и парк дают представление о прошлом местечка.</p>',
                'address' => 'ул. Советская, Логойск',
                'facts' => [
                    ['label' => 'Период', 'value' => 'XVIII–XIX вв.'],
                    ['label' => 'Тип', 'value' => 'усадебно-парковый ансамбль'],
                ],
                'guestTips' => [
                    'Удобно совместить с прогулкой по центру Логойска.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Костёл Святого Казимира',
                'nameGenitive' => 'Костёла Святого Казимира',
                'slug' => 'kostyol-svyatogo-kazimira',
                'latitude' => 54.2008,
                'longitude' => 27.8480,
                'category' => 'sight',
                'shortDescription' => 'Католический храм в центре Логойска — спокойная архитектурная точка городка.',
                'description' => '<p>Костёл Святого Казимира — действующий католический храм. Вместе с окрестным парком и усадебными местами он формирует исторический облик Логойска.</p>',
                'address' => 'ул. Советская, Логойск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'католический храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'После осмотра удобно пройтись по парку у усадьбы.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Церковь Святителя Николая',
                'nameGenitive' => 'Церкви Святителя Николая',
                'slug' => 'nikolskaya-tserkov',
                'latitude' => 54.2025,
                'longitude' => 27.8520,
                'category' => 'sight',
                'shortDescription' => 'Православный храм Логойска — часть прогулочного маршрута по центру местечка.',
                'description' => '<p>Свято-Никольская церковь — действующий православный храм. Рядом исторические улицы и выезды к горнолыжному комплексу.</p>',
                'address' => 'ул. Советская, Логойск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'усадьба Тышкевичей'],
                ],
                'guestTips' => [
                    'Удобная остановка по пути на курорт «Логойск».',
                ],
                'sortOrder' => 40,
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
    private static function orshaLandmarks(): array
    {
        return [
            [
                'name' => 'Кутеинский монастырь',
                'nameGenitive' => 'Кутеинского монастыря',
                'slug' => 'kuteinskiy-monastyr',
                'latitude' => 54.5085,
                'longitude' => 30.4050,
                'category' => 'sight',
                'shortDescription' => 'Богоявленский Кутеинский монастырь XVII века — главная духовная и историческая достопримечательность Орши.',
                'description' => '<p>Кутеинский монастырь известен как центр книгопечатания и православной культуры. Комплекс расположен у реки и входит в обязательный маршрут гостей Орши.</p>',
                'address' => 'ул. Кутеинская, Орша',
                'facts' => [
                    ['label' => 'Основан', 'value' => '1623'],
                    ['label' => 'Река', 'value' => 'Днепр'],
                ],
                'guestTips' => [
                    'Выделите время на прогулку по территории монастыря.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Иезуитский коллегиум',
                'nameGenitive' => 'Иезуитского коллегиума',
                'slug' => 'iezuitskiy-kollegium',
                'latitude' => 54.5115,
                'longitude' => 30.4210,
                'category' => 'sight',
                'shortDescription' => 'Исторический комплекс иезуитов в центре Орши — памятник архитектуры и городская доминанта.',
                'description' => '<p>Здания бывшего иезуитского коллегиума сохраняют атмосферу старой Орши. Рядом удобные прогулочные маршруты к Днепру и центру города.</p>',
                'address' => 'ул. Ленина, Орша',
                'facts' => [
                    ['label' => 'Период', 'value' => 'XVII–XVIII вв.'],
                    ['label' => 'Стиль', 'value' => 'барокко'],
                ],
                'guestTips' => [
                    'Удобно совместить с визитом в Кутеинский монастырь.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Памятник Оршанской битве',
                'nameGenitive' => 'памятника Оршанской битве',
                'slug' => 'pamyatnik-orshanskoy-bitve',
                'latitude' => 54.5140,
                'longitude' => 30.4180,
                'category' => 'sight',
                'shortDescription' => 'Мемориал в честь победы 1514 года — символ военной истории Орши.',
                'description' => '<p>Памятник Оршанской битве напоминает о сражении войск Великого княжества Литовского. Это популярная точка для фотографий и короткой остановки в центре.</p>',
                'address' => 'пр. Заслонова, Орша',
                'facts' => [
                    ['label' => 'Событие', 'value' => '1514 год'],
                    ['label' => 'Тип', 'value' => 'мемориал'],
                ],
                'guestTips' => [
                    'Рядом удобные выезды к вокзалу и монастырю.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Орша-Центральная',
                'nameGenitive' => 'вокзала Орша-Центральная',
                'slug' => 'vokzal-orsha',
                'latitude' => 54.5080,
                'longitude' => 30.4250,
                'category' => 'station',
                'shortDescription' => 'Крупный железнодорожный узел на востоке Беларуси — удобная точка для транзитных гостей.',
                'description' => '<p>Орша-Центральная связывает Минск, Москву, Витебск и другие направления. До исторического центра и монастыря — короткая поездка на такси.</p>',
                'address' => 'пл. Привокзальная, 1, Орша',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Для ночных поездов удобно снимать жильё рядом с вокзалом.',
                ],
                'sortOrder' => 40,
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
    private static function novopolotskLandmarks(): array
    {
        return [
            [
                'name' => 'Дворец культуры нефтехимиков',
                'nameGenitive' => 'Дворца культуры нефтехимиков',
                'slug' => 'dvorets-kultury-neftekhimikov',
                'latitude' => 55.5320,
                'longitude' => 28.6505,
                'category' => 'sight',
                'shortDescription' => 'Главная культурная площадка Новополоцка — концерты, спектакли и городские мероприятия.',
                'description' => '<p>Дворец культуры нефтехимиков — узнаваемый символ молодого промышленного города. Рядом прогулочные зоны и остановки общественного транспорта.</p>',
                'address' => 'пр. Молодёжи, Новополоцк',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'дворец культуры'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Проверьте афишу перед визитом.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Набережная Западной Двины',
                'nameGenitive' => 'набережной Западной Двины',
                'slug' => 'naberezhnaya-zapadnoy-dviny',
                'latitude' => 55.5310,
                'longitude' => 28.6450,
                'category' => 'park',
                'shortDescription' => 'Прогулочная набережная с видом на Западную Двину — популярное место отдыха новополочан.',
                'description' => '<p>Набережная Западной Двины — лучшее место для вечерней прогулки в Новополоцке. Отсюда открываются виды на реку и зелёные берега.</p>',
                'address' => 'наб. Западной Двины, Новополоцк',
                'facts' => [
                    ['label' => 'Река', 'value' => 'Западная Двина'],
                    ['label' => 'Сезон', 'value' => 'особенно популярна летом'],
                ],
                'guestTips' => [
                    'На закате особенно красивые фотографии.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Свято-Ефросиниевская церковь',
                'nameGenitive' => 'Свято-Ефросиниевской церкви',
                'slug' => 'efrosinievskaya-tserkov',
                'latitude' => 55.5335,
                'longitude' => 28.6550,
                'category' => 'sight',
                'shortDescription' => 'Православный храм Новополоцка — спокойная точка в маршруте по городу.',
                'description' => '<p>Свято-Ефросиниевская церковь — действующий православный храм. Удобно посетить вместе с прогулкой по центру и набережной.</p>',
                'address' => 'ул. Молодёжная, Новополоцк',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Совместите визит с прогулкой к Двине.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Парк культуры и отдыха',
                'nameGenitive' => 'парка культуры и отдыха',
                'slug' => 'park-kultury',
                'latitude' => 55.5340,
                'longitude' => 28.6480,
                'category' => 'park',
                'shortDescription' => 'Городской парк с аллеями и зонами отдыха — удобное место для спокойной прогулки.',
                'description' => '<p>Парк культуры и отдыха — зелёное сердце Новополоцка. Летом здесь много семей с детьми, зимой приятно пройтись по аллеям.</p>',
                'address' => 'пр. Молодёжи, Новополоцк',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'городской парк'],
                    ['label' => 'Рядом', 'value' => 'Дворец культуры'],
                ],
                'guestTips' => [
                    'Для спокойной прогулки лучше приезжать утром.',
                ],
                'sortOrder' => 40,
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
    private static function svetlogorskLandmarks(): array
    {
        return [
            [
                'name' => 'Дворец культуры',
                'nameGenitive' => 'Дворца культуры',
                'slug' => 'dvorets-kultury',
                'latitude' => 52.6325,
                'longitude' => 29.3340,
                'category' => 'sight',
                'shortDescription' => 'Главная культурная площадка Светлогорска — концерты, праздники и городские события.',
                'description' => '<p>Дворец культуры Светлогорска — центр общественной жизни города. Рядом парк, площадь и основные прогулочные маршруты.</p>',
                'address' => 'ул. Ленинская, Светлогорск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'дворец культуры'],
                    ['label' => 'Рядом', 'value' => 'городская площадь'],
                ],
                'guestTips' => [
                    'Проверьте афишу мероприятий перед визитом.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Городской парк',
                'nameGenitive' => 'городского парка',
                'slug' => 'gorodskoy-park',
                'latitude' => 52.6335,
                'longitude' => 29.3360,
                'category' => 'park',
                'shortDescription' => 'Зелёный парк в центре Светлогорска — аллеи, зоны отдыха и прогулки у воды.',
                'description' => '<p>Городской парк — любимое место отдыха светлогорчан. Здесь удобно гулять с детьми и отдыхать после дороги.</p>',
                'address' => 'ул. Ленинская, Светлогорск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'городской парк'],
                    ['label' => 'Сезон', 'value' => 'особенно популярен летом'],
                ],
                'guestTips' => [
                    'Утром в парке спокойнее, чем вечером.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Церковь Преображения Господня',
                'nameGenitive' => 'Церкви Преображения Господня',
                'slug' => 'preobrazhenskaya-tserkov',
                'latitude' => 52.6310,
                'longitude' => 29.3320,
                'category' => 'sight',
                'shortDescription' => 'Православный храм Светлогорска — спокойная точка в маршруте по центру города.',
                'description' => '<p>Преображенская церковь — действующий православный храм. Удобно посетить вместе с прогулкой по парку и площади.</p>',
                'address' => 'ул. Советская, Светлогорск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Совместите визит с прогулкой по городскому парку.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Светлогорск',
                'nameGenitive' => 'вокзала Светлогорск',
                'slug' => 'vokzal-svetlogorsk',
                'latitude' => 52.6280,
                'longitude' => 29.3280,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал Светлогорска — удобная точка для гостей, приезжающих на поезде.',
                'description' => '<p>Светлогорский вокзал обслуживает сообщение с Гомелем, Жлобином и другими городами. До центра — короткая поездка на такси или автобусе.</p>',
                'address' => 'пл. Привокзальная, Светлогорск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Для раннего поезда удобно остановиться рядом с вокзалом.',
                ],
                'sortOrder' => 40,
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
    private static function smorgonLandmarks(): array
    {
        return [
            [
                'name' => 'Костёл Святого Михаила',
                'nameGenitive' => 'Костёла Святого Михаила',
                'slug' => 'kostyol-svyatogo-mikhaila',
                'latitude' => 54.4815,
                'longitude' => 26.4005,
                'category' => 'sight',
                'shortDescription' => 'Католический храм в центре Сморгони — архитектурный символ города.',
                'description' => '<p>Костёл Святого Михаила — одна из главных достопримечательностей Сморгони. Храм и окрестные улицы хорошо подходят для короткой прогулки по центру.</p>',
                'address' => 'ул. Советская, Сморгонь',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'католический храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Вечером фасад особенно эффектен при подсветке.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Церковь Преображения Господня',
                'nameGenitive' => 'Церкви Преображения Господня',
                'slug' => 'preobrazhenskaya-tserkov',
                'latitude' => 54.4830,
                'longitude' => 26.4030,
                'category' => 'sight',
                'shortDescription' => 'Православный храм Сморгони — спокойная точка маршрута по исторической части.',
                'description' => '<p>Преображенская церковь — действующий православный храм. Удобно посетить вместе с костёлом и прогулкой по центральным улицам.</p>',
                'address' => 'ул. Ленина, Сморгонь',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'костёл Святого Михаила'],
                ],
                'guestTips' => [
                    'Удобно осмотреть оба храма за одну прогулку.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Памятник медведю',
                'nameGenitive' => 'памятника медведю',
                'slug' => 'pamyatnik-medvedyu',
                'latitude' => 54.4820,
                'longitude' => 26.4015,
                'category' => 'sight',
                'shortDescription' => 'Городской символ Сморгони — отсылка к исторической «школе медведей» и местному фольклору.',
                'description' => '<p>Памятник медведю напоминает о знаменитой сморгонской школе дрессировки. Это популярная точка для фотографий в центре города.</p>',
                'address' => 'центр города, Сморгонь',
                'facts' => [
                    ['label' => 'Символ', 'value' => 'сморгонский медведь'],
                    ['label' => 'Тип', 'value' => 'городской памятник'],
                ],
                'guestTips' => [
                    'Короткая и узнаваемая остановка для фото.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Сморгонь',
                'nameGenitive' => 'вокзала Сморгонь',
                'slug' => 'vokzal-smorgon',
                'latitude' => 54.4795,
                'longitude' => 26.3950,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал на направлении Минск — Вильнюс — удобен для гостей, приезжающих на поезде.',
                'description' => '<p>Сморгонский вокзал расположен недалеко от центра. Отсюда удобно добраться до храмов и основных улиц города.</p>',
                'address' => 'пл. Привокзальная, Сморгонь',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут пешком'],
                ],
                'guestTips' => [
                    'Для короткой поездки удобно выбрать жильё рядом с вокзалом.',
                ],
                'sortOrder' => 40,
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
    private static function zhlobinLandmarks(): array
    {
        return [
            [
                'name' => 'Вокзал Жлобин',
                'nameGenitive' => 'вокзала Жлобин',
                'slug' => 'vokzal-zhlobin',
                'latitude' => 52.8920,
                'longitude' => 30.0250,
                'category' => 'station',
                'shortDescription' => 'Крупный железнодорожный узел на пересечении направлений — главная транспортная точка Жлобина.',
                'description' => '<p>Жлобинский вокзал связывает Минск, Гомель, Могилёв и другие города. Удобен для транзитных гостей и поездок по югу Беларуси.</p>',
                'address' => 'пл. Привокзальная, 1, Жлобин',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'Значение', 'value' => 'крупный узел'],
                ],
                'guestTips' => [
                    'В дни пересадок спрос на жильё рядом с вокзалом растёт.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Свято-Троицкий собор',
                'nameGenitive' => 'Свято-Троицкого собора',
                'slug' => 'troitskiy-sobor',
                'latitude' => 52.8955,
                'longitude' => 30.0380,
                'category' => 'sight',
                'shortDescription' => 'Православный собор в центре Жлобина — архитектурная доминанта города.',
                'description' => '<p>Свято-Троицкий собор — главный храм Жлобина. Белые стены и купола хорошо смотрятся на фоне центральной застройки.</p>',
                'address' => 'ул. Первомайская, Жлобин',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный собор'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Удобно совместить с прогулкой по центральным улицам.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Парк культуры и отдыха',
                'nameGenitive' => 'парка культуры и отдыха',
                'slug' => 'park-kultury',
                'latitude' => 52.8970,
                'longitude' => 30.0400,
                'category' => 'park',
                'shortDescription' => 'Городской парк с аллеями и зонами отдыха — популярное место прогулок в Жлобине.',
                'description' => '<p>Парк культуры и отдыха — зелёное пространство в центре города. Летом здесь гуляют семьи, зимой приятно пройтись по аллеям.</p>',
                'address' => 'ул. Первомайская, Жлобин',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'городской парк'],
                    ['label' => 'Сезон', 'value' => 'особенно популярен летом'],
                ],
                'guestTips' => [
                    'Для спокойной прогулки лучше приезжать утром.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Мемориал Великой Отечественной войны',
                'nameGenitive' => 'мемориала Великой Отечественной войны',
                'slug' => 'memorial-vov',
                'latitude' => 52.8940,
                'longitude' => 30.0360,
                'category' => 'sight',
                'shortDescription' => 'Городской мемориал памяти — тихая точка для короткой остановки в центре Жлобина.',
                'description' => '<p>Мемориальный комплекс напоминает о событиях войны и освобождении города. Рядом удобные маршруты к собору и парку.</p>',
                'address' => 'центр города, Жлобин',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'мемориал'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Короткая и важная остановка в городском маршруте.',
                ],
                'sortOrder' => 40,
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
    private static function volkovyskLandmarks(): array
    {
        return [
            [
                'name' => 'Костёл Святого Вацлава',
                'nameGenitive' => 'Костёла Святого Вацлава',
                'slug' => 'kostyol-svyatogo-vatslava',
                'latitude' => 53.1565,
                'longitude' => 24.4510,
                'category' => 'sight',
                'shortDescription' => 'Католический храм в центре Волковыска — одна из главных архитектурных доминант города.',
                'description' => '<p>Костёл Святого Вацлава — действующий католический храм. Фасад и башня хорошо смотрятся на фоне исторической части Волковыска.</p>',
                'address' => 'ул. Советская, Волковыск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'католический храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Удобно совместить с посещением музея и православного храма.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Церковь Святых Петра и Павла',
                'nameGenitive' => 'Церкви Святых Петра и Павла',
                'slug' => 'tserkov-petra-i-pavla',
                'latitude' => 53.1580,
                'longitude' => 24.4535,
                'category' => 'sight',
                'shortDescription' => 'Православный храм Волковыска — спокойная точка прогулочного маршрута по центру.',
                'description' => '<p>Петропавловская церковь — действующий православный храм. Вместе с костёлом формирует архитектурный облик центральной части города.</p>',
                'address' => 'ул. Ленина, Волковыск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'костёл Святого Вацлава'],
                ],
                'guestTips' => [
                    'Оба храма удобно осмотреть за одну прогулку.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Краеведческий музей',
                'nameGenitive' => 'краеведческого музея',
                'slug' => 'kraevedcheskiy-muzey',
                'latitude' => 53.1570,
                'longitude' => 24.4495,
                'category' => 'sight',
                'shortDescription' => 'Музей истории Волковыска и района — короткое знакомство с прошлым города.',
                'description' => '<p>Волковысский краеведческий музей рассказывает о древнем городе на западе Беларуси, археологии и местной культуре.</p>',
                'address' => 'ул. Советская, Волковыск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'краеведческий музей'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Проверьте режим работы перед визитом.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Волковыск',
                'nameGenitive' => 'вокзала Волковыск',
                'slug' => 'vokzal-volkovysk',
                'latitude' => 53.1540,
                'longitude' => 24.4450,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал Волковыска — удобная точка для гостей, приезжающих на поезде.',
                'description' => '<p>Волковысский вокзал обслуживает сообщение с Гродно и другими городами. До центра и храмов — короткая поездка на такси или пешком.</p>',
                'address' => 'пл. Привокзальная, Волковыск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут пешком'],
                ],
                'guestTips' => [
                    'Для раннего отъезда удобно остановиться рядом с вокзалом.',
                ],
                'sortOrder' => 40,
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
    private static function novolukomlLandmarks(): array
    {
        return [
            [
                'name' => 'Озеро Лукомльское',
                'nameGenitive' => 'озера Лукомльского',
                'slug' => 'ozero-lukomlskoe',
                'latitude' => 54.6610,
                'longitude' => 29.1520,
                'category' => 'park',
                'shortDescription' => 'Крупное озеро у Новолукомля — главная природная достопримечательность и место отдыха у воды.',
                'description' => '<p>Лукомльское озеро — визитная карточка города. Летом здесь гуляют по берегу, фотографируют воду и отдыхают на свежем воздухе.</p>',
                'address' => 'берег озера Лукомльского, Новолукомль',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'озеро'],
                    ['label' => 'Сезон', 'value' => 'пик — лето'],
                ],
                'guestTips' => [
                    'На закате особенно красивые виды на воду.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Набережная озера',
                'nameGenitive' => 'набережной озера',
                'slug' => 'naberezhnaya-ozera',
                'latitude' => 54.6595,
                'longitude' => 29.1505,
                'category' => 'park',
                'shortDescription' => 'Прогулочная набережная у Лукомльского озера — популярное место вечернего отдыха.',
                'description' => '<p>Набережная у озера — лучший маршрут для спокойной прогулки в Новолукомле. Рядом зелёные зоны и виды на воду.</p>',
                'address' => 'наб. озера Лукомльского, Новолукомль',
                'facts' => [
                    ['label' => 'Рядом', 'value' => 'озеро Лукомльское'],
                    ['label' => 'Сезон', 'value' => 'особенно популярна летом'],
                ],
                'guestTips' => [
                    'Удобно совместить с отдыхом у озера.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Церковь Святителя Николая',
                'nameGenitive' => 'Церкви Святителя Николая',
                'slug' => 'nikolskaya-tserkov',
                'latitude' => 54.6570,
                'longitude' => 29.1480,
                'category' => 'sight',
                'shortDescription' => 'Православный храм Новолукомля — спокойная точка в маршруте по городу.',
                'description' => '<p>Свято-Никольская церковь — действующий православный храм. Удобно посетить вместе с прогулкой к озеру.</p>',
                'address' => 'ул. Центральная, Новолукомль',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'После осмотра удобно спуститься к набережной озера.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Городской парк',
                'nameGenitive' => 'городского парка',
                'slug' => 'gorodskoy-park',
                'latitude' => 54.6580,
                'longitude' => 29.1490,
                'category' => 'park',
                'shortDescription' => 'Небольшой городской парк — зелёная зона отдыха рядом с центром Новолукомля.',
                'description' => '<p>Городской парк подходит для короткой прогулки и отдыха с детьми. Отсюда удобно продолжить маршрут к озеру.</p>',
                'address' => 'центр города, Новолукомль',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'городской парк'],
                    ['label' => 'Рядом', 'value' => 'озеро Лукомльское'],
                ],
                'guestTips' => [
                    'Утром в парке спокойнее.',
                ],
                'sortOrder' => 40,
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
    private static function krichevLandmarks(): array
    {
        return [
            [
                'name' => 'Усадьба Потёмкиных',
                'nameGenitive' => 'усадьбы Потёмкиных',
                'slug' => 'usadba-potyomkinyh',
                'latitude' => 53.7105,
                'longitude' => 31.7170,
                'category' => 'sight',
                'shortDescription' => 'Дворцово-парковый ансамбль XVIII века — главная историческая достопримечательность Кричева.',
                'description' => '<p>Усадьба Потёмкиных — редкий для Могилёвщины дворцовый комплекс. Здание и парк сохраняют атмосферу уездного Кричева екатерининской эпохи.</p>',
                'address' => 'ул. Ленина, Кричев',
                'facts' => [
                    ['label' => 'Период', 'value' => 'конец XVIII века'],
                    ['label' => 'Стиль', 'value' => 'классицизм'],
                ],
                'guestTips' => [
                    'Лучшие фото — со стороны парка.',
                ],
                'sortOrder' => 10,
            ],
            [
                'name' => 'Свято-Никольская церковь',
                'nameGenitive' => 'Свято-Никольской церкви',
                'slug' => 'nikolskaya-tserkov',
                'latitude' => 53.7120,
                'longitude' => 31.7195,
                'category' => 'sight',
                'shortDescription' => 'Православный храм Кричева — спокойная точка в маршруте по центру города.',
                'description' => '<p>Свято-Никольская церковь — действующий православный храм. Удобно посетить вместе с усадьбой Потёмкиных и прогулкой к Сожу.</p>',
                'address' => 'ул. Советская, Кричев',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'православный храм'],
                    ['label' => 'Рядом', 'value' => 'центр города'],
                ],
                'guestTips' => [
                    'Совместите визит с осмотром усадьбы.',
                ],
                'sortOrder' => 20,
            ],
            [
                'name' => 'Набережная реки Сож',
                'nameGenitive' => 'набережной реки Сож',
                'slug' => 'naberezhnaya-sozh',
                'latitude' => 53.7090,
                'longitude' => 31.7150,
                'category' => 'park',
                'shortDescription' => 'Прогулочная зона у реки Сож — популярное место отдыха кричевлян и гостей города.',
                'description' => '<p>Набережная Сожа даёт виды на воду и зелёные берега. Приятное место для вечерней прогулки после осмотра усадьбы.</p>',
                'address' => 'наб. Сожа, Кричев',
                'facts' => [
                    ['label' => 'Река', 'value' => 'Сож'],
                    ['label' => 'Сезон', 'value' => 'особенно популярна летом'],
                ],
                'guestTips' => [
                    'На закате особенно красивые виды на реку.',
                ],
                'sortOrder' => 30,
            ],
            [
                'name' => 'Вокзал Кричев',
                'nameGenitive' => 'вокзала Кричев',
                'slug' => 'vokzal-krichev',
                'latitude' => 53.7065,
                'longitude' => 31.7100,
                'category' => 'station',
                'shortDescription' => 'Железнодорожный вокзал Кричева — удобная точка для гостей, приезжающих на поезде.',
                'description' => '<p>Кричевский вокзал обслуживает сообщение с Могилёвом и другими городами. До центра и усадьбы — короткая поездка на такси.</p>',
                'address' => 'пл. Привокзальная, Кричев',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
                    ['label' => 'До центра', 'value' => 'около 10 минут на машине'],
                ],
                'guestTips' => [
                    'Для раннего поезда удобно остановиться рядом с вокзалом.',
                ],
                'sortOrder' => 40,
            ],
        ];
    }
}
