<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260809130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fix Brest railway station landmark: address, coordinates and heritage description';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot update landmark.');

        $facts = json_encode([
            ['label' => 'Открыт', 'value' => '1886'],
            ['label' => 'Стиль', 'value' => 'сталинский ампир (1953—1957)'],
            ['label' => 'Статус', 'value' => 'историко-культурная ценность Беларуси'],
            ['label' => 'Особенность', 'value' => 'пограничная станция, стык колей'],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $guestTips = json_encode([
            'Внутри — богатая мраморная отделка: многие называют вокзал «музеем мрамора».',
            'Удобно снимать жильё рядом при ночных и ранних поездах и при поездках в Польшу.',
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
                'Вокзал Брест-Центральный',
                'вокзала Брест-Центральный',
                52.100278,
                23.680556,
                'Железнодорожный вокзал Брест-Центральный — памятник архитектуры и историко-культурная ценность Беларуси, «ворота» города на границе с Европой.',
                '<p>Вокзал станции Брест-Центральный — один из символов города и важный пограничный узел Белорусской железной дороги. Станция открыта в 1886 году; современный облик здание получило в 1953—1957 годах в стиле сталинского ампира: колоннады, шпиль со звездой, богатая отделка гранитом и мрамором из разных регионов СССР — поэтому вокзал часто называют «музеем мрамора».</p><p>Комплекс вокзала со зданием железнодорожного почтамта и администрации внесён в Государственный список историко-культурных ценностей Республики Беларусь (категория 2). Здесь стыкуются восточная и европейская колеи — удобная точка для гостей, прибывающих поездом из Минска, Польши и дальше в Европу.</p>',
                'пл. Привокзальная, 1, Брест',
                $facts,
                $guestTips,
                (int) $cityId,
                'vokzal-brest',
            ],
        );
    }

    public function down(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'brest'");
        $this->abortIf($cityId === false, 'City "brest" not found — cannot revert landmark.');

        $facts = json_encode([
            ['label' => 'Тип', 'value' => 'железнодорожный вокзал'],
            ['label' => 'Направления', 'value' => 'Минск, Польша'],
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $guestTips = json_encode([
            'Удобно снимать жильё рядом при ночных и ранних поездах.',
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
                'Вокзал Брест',
                'вокзала Брест',
                52.1013,
                23.6558,
                'Железнодорожный вокзал на пограничном направлении — удобная точка для транзитных гостей и поездок в Европу.',
                '<p>Брестский вокзал — важный транспортный узел на западе страны. Рядом остановки общественного транспорта и сервисы для путешественников.</p>',
                'пл. Ленина, 3, Брест',
                $facts,
                $guestTips,
                (int) $cityId,
                'vokzal-brest',
            ],
        );
    }
}
