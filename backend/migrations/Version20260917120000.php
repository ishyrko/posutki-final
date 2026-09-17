<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Самостоятельные города/г.п. с опубликованными квартирами — в подсказках формы и на главной.
 * Исключены населённые пункты в районе областного центра (Заславль, Мачулищи в Минском районе)
 * и города в районе другого райцентра (Фаниполь в Дзержинском, Микашевичи в Лунинецком).
 */
final class Version20260917120000 extends AbstractMigration
{
    /** Уже были в подсказках формы — включаем только каталог/главную. */
    private const ENABLE_CATALOG_ONLY = [
        'soligorsk',
        'ostrovets',
        'polotsk',
        'borisov',
        'kobrin',
        'slonim',
    ];

    /** Новые самостоятельные райцентры с объявлениями. */
    private const ENABLE_BOTH = [
        'lyuban-g',
        'petrikov-g',
        'slutsk-g',
        'smolevichi-g',
        'osipovichi-g',
        'pruzhany-g',
        'kalinkovichi-g',
        'oshmyany-g',
        'stolbtsy-g',
        'bereza-g',
        'vileyka-g',
        'gantsevichi-g',
        'dzerzhinsk-g',
        'ivatsevichi-g',
        'kostyukovichi-g',
        'miory-g',
        'novogrudok-g',
        'rogachev-g',
        'stolin-g',
        'hoyniki-g',
        'bragin-gp',
        'dokshitsy-g',
        'zhabinka-g',
        'zhitkovichi-g',
        'ivanovo-g',
        'korma-gp',
        'lepel-g',
        'marina-gorka-g',
        'mstislavl-g',
        'myadel-g',
        'postavy-g',
        'svisloch-g',
        'buda-koshelevo-g',
        'verhnedvinsk-g',
        'volozhin-g',
        'gorki-g',
        'drogichin-g',
        'dyatlovo-g',
        'elsk-g',
        'zelva-gp',
        'ive-g',
        'kamenets-g',
        'kletsk-g',
        'klimovichi-g',
        'lelchitsy-gp',
        'luninets-g',
        'lyahovichi-g',
        'mosty-g',
        'senno-g',
        'hotimsk-gp',
        'chechersk-g',
        'schuchin-g',
    ];

    public function getDescription(): string
    {
        return 'Enable listing suggestions and apartment catalog for independent towns that have published apartments';
    }

    public function up(Schema $schema): void
    {
        $this->updateFlags(self::ENABLE_BOTH, suggested: true, catalog: true);
        $this->updateFlags(self::ENABLE_CATALOG_ONLY, suggested: null, catalog: true);
    }

    public function down(Schema $schema): void
    {
        $this->updateFlags(self::ENABLE_BOTH, suggested: false, catalog: false);
        $this->updateFlags(self::ENABLE_CATALOG_ONLY, suggested: null, catalog: false);
    }

    /**
     * @param list<string> $slugs
     */
    private function updateFlags(array $slugs, ?bool $suggested, bool $catalog): void
    {
        $set = ['is_apartment_catalog = ' . ($catalog ? '1' : '0')];
        if ($suggested !== null) {
            $set[] = 'is_listing_suggested = ' . ($suggested ? '1' : '0');
        }

        $placeholders = implode(', ', array_fill(0, count($slugs), '?'));
        $this->addSql(
            sprintf('UPDATE cities SET %s WHERE slug IN (%s)', implode(', ', $set), $placeholders),
            $slugs,
        );
    }
}
