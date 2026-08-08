<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260808150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix Brest landmark Свято-Николаевский собор: coordinates/address were city centre, description is fortress garrison cathedral';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot fix landmark.');

        $facts = json_encode([
            ['label' => 'Построен', 'value' => '1856—1879'],
            ['label' => 'Стиль', 'value' => 'неовизантийский'],
            ['label' => 'Архитектор', 'value' => 'Давид Гримм'],
            ['label' => 'Расположение', 'value' => 'Брестская крепость'],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $guestTips = json_encode([
            'Собор стоит в центре цитадели, за монументом «Мужество» — удобно совместить с посещением крепости.',
            'Рядом музей «Берестье» и музей железнодорожной техники у входа в крепость.',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->addSql(
            'UPDATE landmarks SET
                name = ?,
                name_genitive = ?,
                latitude = ?,
                longitude = ?,
                short_description = ?,
                description = ?,
                address = ?,
                facts = ?,
                guest_tips = ?
             WHERE city_id = ? AND slug = ?',
            [
                'Свято-Николаевский гарнизонный собор',
                'Свято-Николаевского гарнизонного собора',
                52.082908,
                23.654981,
                'Православный храм XIX века в центре Брестской крепости — неовизантийский собор с белыми стенами и золотыми куполами.',
                '<p>Свято-Николаевский гарнизонный собор находится на территории мемориального комплекса «Брестская крепость-герой». Храм построен в 1856—1879 годах по проекту архитектора Давида Гримма в неовизантийском стиле.</p><p>В XX веке собор сильно пострадал, долго стоял в руинах и был восстановлен к началу 2000-х. Сегодня это одна из главных точек маршрута по крепости — рядом монумент «Мужество» и музейные экспозиции.</p>',
                'ул. Героев обороны Брестской крепости, 60И, Брест',
                $facts,
                $guestTips,
                (int) $cityId,
                'sobor-svyatogo-nikolaya',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot revert landmark.');

        $facts = json_encode([
            ['label' => 'Построен', 'value' => '1876'],
            ['label' => 'Стиль', 'value' => 'византийский'],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $guestTips = json_encode([
            'Рядом пешеходная ул. Советская с кафе и сувенирными лавками.',
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $this->addSql(
            'UPDATE landmarks SET
                name = ?,
                name_genitive = ?,
                latitude = ?,
                longitude = ?,
                short_description = ?,
                description = ?,
                address = ?,
                facts = ?,
                guest_tips = ?
             WHERE city_id = ? AND slug = ?',
            [
                'Свято-Николаевский собор',
                'Свято-Николаевского собора',
                52.0898,
                23.6847,
                'Белокаменный православный собор XIX века — одна из главных архитектурных доминант центра Бреста.',
                '<p>Свято-Николаевский гарнизонный собор возведён в 1870-х годах. Высокие купола хорошо видны из разных точек города и особенно эффектны на фоне заката.</p>',
                'ул. Советская, 2, Брест',
                $facts,
                $guestTips,
                (int) $cityId,
                'sobor-svyatogo-nikolaya',
            ],
        );
    }
}
