<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809120100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix Brest garrison cathedral guest tip about railway museum location';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot update landmark.');

        $guestTips = json_encode([
            'Собор стоит в центре цитадели, за монументом «Мужество» — удобно совместить с посещением крепости.',
            'Рядом музей «Берестье»; музей железнодорожной техники — на пр. Машерова по дороге к крепости.',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->addSql(
            'UPDATE landmarks SET guest_tips = ? WHERE city_id = ? AND slug = ?',
            [$guestTips, (int) $cityId, 'sobor-svyatogo-nikolaya'],
        );
    }

    public function down(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot revert landmark.');

        $guestTips = json_encode([
            'Собор стоит в центре цитадели, за монументом «Мужество» — удобно совместить с посещением крепости.',
            'Рядом музей «Берестье» и музей железнодорожной техники у входа в крепость.',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->addSql(
            'UPDATE landmarks SET guest_tips = ? WHERE city_id = ? AND slug = ?',
            [$guestTips, (int) $cityId, 'sobor-svyatogo-nikolaya'],
        );
    }
}
