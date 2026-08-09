<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix Brest railway museum copy: not at fortress main entrance';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot update landmark.');

        $guestTips = json_encode([
            'Удобно совместить с посещением Брестской крепости — музей на пр. Машерова по дороге к мемориалу.',
            'Выходной день обычно понедельник — уточняйте режим перед визитом.',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->addSql(
            'UPDATE landmarks SET
                short_description = ?,
                description = ?,
                guest_tips = ?
             WHERE city_id = ? AND slug = ?',
            [
                'Открытая экспозиция паровозов, вагонов и железнодорожной техники на пр. Машерова — один из главных технических музеев Беларуси.',
                '<p>Брестский музей железнодорожной техники основан в 2002 году. На открытой площадке собраны десятки локомотивов, вагонов и служебных машин разных эпох — многие экспонаты действующие.</p><p>Музей расположен на проспекте Машерова, 2, по дороге к мемориальному комплексу «Брестская крепость-герой» (около 1–1,5 км пешком до цитадели) — удобно совместить технический и исторический маршруты в один день.</p>',
                $guestTips,
                (int) $cityId,
                'muzey-zheleznodorozhnoy-tehniki',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot revert landmark.');

        $guestTips = json_encode([
            'Удобно посетить в один день с Брестской крепостью — музей у главного входа.',
            'Выходной день обычно понедельник — уточняйте режим перед визитом.',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->addSql(
            'UPDATE landmarks SET
                short_description = ?,
                description = ?,
                guest_tips = ?
             WHERE city_id = ? AND slug = ?',
            [
                'Открытая экспозиция паровозов, вагонов и железнодорожной техники у Брестской крепости — один из главных технических музеев Беларуси.',
                '<p>Брестский музей железнодорожной техники основан в 2002 году. На открытой площадке собраны десятки локомотивов, вагонов и служебных машин разных эпох — многие экспонаты действующие.</p><p>Музей расположен на проспекте Машерова, 2, рядом со входом в Брестскую крепость — удобная точка для гостей, которые хотят совместить исторический и технический маршруты.</p>',
                $guestTips,
                (int) $cityId,
                'muzey-zheleznodorozhnoy-tehniki',
            ],
        );
    }
}
