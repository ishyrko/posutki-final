<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Для городов каталога на главной: предложный/родительный падежи
 * и без суффикса « г.» в названии и slug. «г.п.», «п.», «к.п.» не трогаем.
 */
final class Version20260917160000 extends AbstractMigration
{
    /** @var array<string, string> */
    private const STRIP_CITY_G_SLUGS = [
        'beloozersk-g' => 'beloozersk',
        'bereza-g' => 'bereza',
        'buda-koshelevo-g' => 'buda-koshelevo',
        'verhnedvinsk-g' => 'verhnedvinsk',
        'vileyka-g' => 'vileyka',
        'volozhin-g' => 'volozhin',
        'gantsevichi-g' => 'gantsevichi',
        'gorki-g' => 'gorki',
        'dzerzhinsk-g' => 'dzerzhinsk',
        'dokshitsy-g' => 'dokshitsy',
        'drogichin-g' => 'drogichin',
        'dyatlovo-g' => 'dyatlovo',
        'elsk-g' => 'elsk',
        'zhabinka-g' => 'zhabinka',
        'zhitkovichi-g' => 'zhitkovichi',
        'ivanovo-g' => 'ivanovo',
        'ivatsevichi-g' => 'ivatsevichi',
        'ive-g' => 'ive',
        'kalinkovichi-g' => 'kalinkovichi',
        'kamenets-g' => 'kamenets',
        'kletsk-g' => 'kletsk',
        'klimovichi-g' => 'klimovichi',
        'kostyukovichi-g' => 'kostyukovichi',
        'lepel-g' => 'lepel',
        'luninets-g' => 'luninets',
        'lyuban-g' => 'lyuban',
        'lyahovichi-g' => 'lyahovichi',
        'marina-gorka-g' => 'marina-gorka',
        'mikashevichi-g' => 'mikashevichi',
        'miory-g' => 'miory',
        'mosty-g' => 'mosty',
        'mstislavl-g' => 'mstislavl',
        'myadel-g' => 'myadel',
        'novogrudok-g' => 'novogrudok',
        'osipovichi-g' => 'osipovichi',
        'oshmyany-g' => 'oshmyany',
        'petrikov-g' => 'petrikov',
        'postavy-g' => 'postavy',
        'pruzhany-g' => 'pruzhany',
        'rogachev-g' => 'rogachev',
        'svisloch-g' => 'svisloch',
        'senno-g' => 'senno',
        'slutsk-g' => 'slutsk',
        'smolevichi-g' => 'smolevichi',
        'stolbtsy-g' => 'stolbtsy',
        'stolin-g' => 'stolin',
        'fanipol-g' => 'fanipol',
        'hoyniki-g' => 'hoyniki',
        'chechersk-g' => 'chechersk',
        'schuchin-g' => 'schuchin',
    ];

    public function getDescription(): string
    {
        return 'Fill catalog city grammatical cases and strip « г.» from homepage city names/slugs';
    }

    public function up(Schema $schema): void
    {
        foreach (self::STRIP_CITY_G_SLUGS as $oldSlug => $newSlug) {
            $row = $this->connection->fetchAssociative(
                'SELECT id, name, short_name FROM cities WHERE slug = ? AND is_apartment_catalog = 1',
                [$oldSlug],
            );
            if ($row === false) {
                continue;
            }

            $id = (int) $row['id'];
            $this->assertSlugFree($newSlug, $id);
            $this->addSql(
                'UPDATE cities SET name = ?, short_name = ?, slug = ? WHERE id = ?',
                [
                    self::stripCityGSuffix((string) $row['name']),
                    self::stripCityGSuffix((string) $row['short_name']),
                    $newSlug,
                    $id,
                ],
            );
        }

        foreach (self::casesBySlug() as $slug => [$prepositional, $genitive]) {
            $this->addSql(
                'UPDATE cities SET name_prepositional = ?, name_genitive = ? WHERE slug = ? AND is_apartment_catalog = 1',
                [$prepositional, $genitive, $slug],
            );
        }
    }

    public function down(Schema $schema): void
    {
        foreach (self::previousCasesBySlug() as $slug => [$prepositional, $genitive]) {
            $this->addSql(
                'UPDATE cities SET name_prepositional = ?, name_genitive = ? WHERE slug = ? AND is_apartment_catalog = 1',
                [$prepositional, $genitive, $slug],
            );
        }

        foreach (self::STRIP_CITY_G_SLUGS as $oldSlug => $newSlug) {
            $this->addSql(
                "UPDATE cities
                 SET name = CONCAT(name, ' г.'),
                     short_name = CONCAT(short_name, ' г.'),
                     slug = ?
                 WHERE slug = ? AND is_apartment_catalog = 1",
                [$oldSlug, $newSlug],
            );
        }
    }

    private function assertSlugFree(string $slug, int $cityId): void
    {
        $taken = $this->connection->fetchOne(
            'SELECT id FROM cities WHERE slug = ? AND id <> ?',
            [$slug, $cityId],
        );
        if ($taken !== false && $taken !== null) {
            throw new \RuntimeException(sprintf(
                'Cannot rename catalog city #%d: slug «%s» already exists (#%s).',
                $cityId,
                $slug,
                (string) $taken,
            ));
        }
    }

    private static function stripCityGSuffix(string $value): string
    {
        return str_ends_with($value, ' г.') ? mb_substr($value, 0, -3) : $value;
    }

    /**
     * Предложный без «в», родительный для «в районе …». Ключ — slug после снятия «-g».
     *
     * @return array<string, array{0: string, 1: string}>
     */
    private static function casesBySlug(): array
    {
        return [
            'baranovichi' => ['Барановичах', 'Барановичей'],
            'beloozersk' => ['Белоозёрске', 'Белоозёрска'],
            'bereza' => ['Берёзе', 'Берёзы'],
            'bobruysk' => ['Бобруйске', 'Бобруйска'],
            'borisov' => ['Борисове', 'Борисова'],
            'bragin-gp' => ['Брагине', 'Брагина'],
            'brest' => ['Бресте', 'Бреста'],
            'buda-koshelevo' => ['Буда-Кошелеве', 'Буда-Кошелева'],
            'verhnedvinsk' => ['Верхнедвинске', 'Верхнедвинска'],
            'vileyka' => ['Вилейке', 'Вилейки'],
            'vitebsk' => ['Витебске', 'Витебска'],
            'volkovysk' => ['Волковыске', 'Волковыска'],
            'volozhin' => ['Воложине', 'Воложина'],
            'gantsevichi' => ['Ганцевичах', 'Ганцевичей'],
            'glubokoe' => ['Глубоком', 'Глубокого'],
            'gomel' => ['Гомеле', 'Гомеля'],
            'gorki' => ['Горках', 'Горок'],
            'grodno' => ['Гродно', 'Гродно'],
            'dzerzhinsk' => ['Дзержинске', 'Дзержинска'],
            'dobrush' => ['Добруше', 'Добруша'],
            'dokshitsy' => ['Докшицах', 'Докшиц'],
            'drogichin' => ['Дрогичине', 'Дрогичина'],
            'druzhnyy-p' => ['Дружном', 'Дружного'],
            'dyatlovo' => ['Дятлове', 'Дятлова'],
            'elsk' => ['Ельске', 'Ельска'],
            'zhabinka' => ['Жабинке', 'Жабинки'],
            'zhitkovichi' => ['Житковичах', 'Житковичей'],
            'zhlobin' => ['Жлобине', 'Жлобина'],
            'zhodino' => ['Жодино', 'Жодино'],
            'zelva-gp' => ['Зельве', 'Зельвы'],
            'ivanovo' => ['Иванове', 'Иванова'],
            'ivatsevichi' => ['Ивацевичах', 'Ивацевичей'],
            'ive' => ['Ивье', 'Ивье'],
            'kalinkovichi' => ['Калинковичах', 'Калинковичей'],
            'kamenets' => ['Каменце', 'Каменца'],
            'kletsk' => ['Клецке', 'Клецка'],
            'klimovichi' => ['Климовичах', 'Климовичей'],
            'kobrin' => ['Кобрине', 'Кобрина'],
            'korma-gp' => ['Корме', 'Кормы'],
            'kostyukovichi' => ['Костюковичах', 'Костюковичей'],
            'krichev' => ['Кричеве', 'Кричева'],
            'lelchitsy-gp' => ['Лельчицах', 'Лельчиц'],
            'lepel' => ['Лепеле', 'Лепеля'],
            'lida' => ['Лиде', 'Лиды'],
            'logoysk' => ['Логойске', 'Логойска'],
            'luninets' => ['Лунинце', 'Лунинца'],
            'lyuban' => ['Любани', 'Любани'],
            'lyahovichi' => ['Ляховичах', 'Ляховичей'],
            'marina-gorka' => ['Марьиной Горке', 'Марьиной Горки'],
            'mikashevichi' => ['Микашевичах', 'Микашевичей'],
            'minsk' => ['Минске', 'Минска'],
            'miory' => ['Миорах', 'Миор'],
            'mogilev' => ['Могилёве', 'Могилёва'],
            'mozyr' => ['Мозыре', 'Мозыря'],
            'molodechno' => ['Молодечно', 'Молодечно'],
            'mosty' => ['Мостах', 'Мостов'],
            'mstislavl' => ['Мстиславле', 'Мстиславля'],
            'myadel' => ['Мяделе', 'Мяделя'],
            'naroch-kp' => ['Курортном поселке Нарочь', 'Нарочи'],
            'nesvizh' => ['Несвиже', 'Несвижа'],
            'novogrudok' => ['Новогрудке', 'Новогрудка'],
            'novolukoml' => ['Новолукомле', 'Новолукомля'],
            'novopolotsk' => ['Новополоцке', 'Новополоцка'],
            'orsha' => ['Орше', 'Орши'],
            'osipovichi' => ['Осиповичах', 'Осиповичей'],
            'ostrovets' => ['Островце', 'Островца'],
            'oshmyany' => ['Ошмянах', 'Ошмян'],
            'petrikov' => ['Петрикове', 'Петрикова'],
            'pinsk' => ['Пинске', 'Пинска'],
            'polotsk' => ['Полоцке', 'Полоцка'],
            'postavy' => ['Поставах', 'Постав'],
            'pruzhany' => ['Пружанах', 'Пружан'],
            'rechitsa' => ['Речице', 'Речицы'],
            'rogachev' => ['Рогачёве', 'Рогачёва'],
            'svetlogorsk' => ['Светлогорске', 'Светлогорска'],
            'svisloch' => ['Свислочи', 'Свислочи'],
            'senno' => ['Сенно', 'Сенно'],
            'slonim' => ['Слониме', 'Слонима'],
            'slutsk' => ['Слуцке', 'Слуцка'],
            'smolevichi' => ['Смолевичах', 'Смолевичей'],
            'smorgon' => ['Сморгони', 'Сморгони'],
            'soligorsk' => ['Солигорске', 'Солигорска'],
            'starye-dorogi' => ['Старых Дорогах', 'Старых Дорог'],
            'stolbtsy' => ['Столбцах', 'Столбцов'],
            'stolin' => ['Столине', 'Столина'],
            'fanipol' => ['Фаниполе', 'Фаниполя'],
            'hoyniki' => ['Хойниках', 'Хойников'],
            'hotimsk-gp' => ['Хотимске', 'Хотимска'],
            'chechersk' => ['Чечерске', 'Чечерска'],
            'schuchin' => ['Щучине', 'Щучина'],
        ];
    }

    /**
     * Падежи до этой миграции (slug уже после снятия «-g»).
     *
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    private static function previousCasesBySlug(): array
    {
        $filled = [
            'baranovichi' => ['Барановичах', null],
            'bobruysk' => ['Бобруйске', 'Бобруйска'],
            'brest' => ['Бресте', 'Бреста'],
            'vitebsk' => ['Витебске', 'Витебска'],
            'volkovysk' => ['Волковыске', null],
            'glubokoe' => ['Глубоком', null],
            'gomel' => ['Гомеле', 'Гомеля'],
            'grodno' => ['Гродно', 'Гродно'],
            'dobrush' => ['Добруше', 'Добруша'],
            'zhlobin' => ['Жлобине', null],
            'zhodino' => ['Жодино', null],
            'kobrin' => ['Кобрине', 'Кобрина'],
            'krichev' => ['Кричеве', null],
            'lida' => ['Лиде', null],
            'logoysk' => ['Логойске', null],
            'minsk' => ['Минске', 'Минска'],
            'mogilev' => ['Могилёве', 'Могилёва'],
            'mozyr' => ['Мозыре', null],
            'molodechno' => ['Молодечно', null],
            'naroch-kp' => ['Курортном поселке Нарочь', null],
            'nesvizh' => ['Несвиже', null],
            'novolukoml' => ['Новолукомле', null],
            'novopolotsk' => ['Новополоцке', null],
            'orsha' => ['Орше', null],
            'pinsk' => ['Пинске', null],
            'svetlogorsk' => ['Светлогорске', null],
            'smorgon' => ['Сморгони', null],
            'starye-dorogi' => ['Старых Дорогах', 'Старых Дорог'],
        ];

        $previous = [];
        foreach (array_keys(self::casesBySlug()) as $slug) {
            $previous[$slug] = $filled[$slug] ?? [null, null];
        }

        return $previous;
    }
}
