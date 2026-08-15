<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260815140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Minsk landmarks: Lebyazhy and Fristail aquaparks (inactive by default)';
    }

    public function up(Schema $schema): void
    {
        $cityId = $this->connection->fetchOne("SELECT id FROM cities WHERE slug = 'minsk'");
        $this->abortIf($cityId === false, 'City "minsk" not found — cannot seed landmarks.');

        $cityId = (int) $cityId;

        foreach ($this->landmarks() as $landmark) {
            $this->addSql(
                'INSERT INTO landmarks (
                    city_id, name, name_genitive, slug, latitude, longitude, category,
                    short_description, description, address, facts, guest_tips, is_active, sort_order
                )
                SELECT ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?
                WHERE NOT EXISTS (
                    SELECT 1 FROM landmarks WHERE city_id = ? AND slug = ?
                )',
                [
                    $cityId,
                    $landmark['name'],
                    $landmark['name_genitive'],
                    $landmark['slug'],
                    $landmark['latitude'],
                    $landmark['longitude'],
                    $landmark['category'],
                    $landmark['short_description'],
                    $landmark['description'],
                    $landmark['address'],
                    json_encode($landmark['facts'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    json_encode($landmark['guest_tips'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                    $landmark['sort_order'],
                    $cityId,
                    $landmark['slug'],
                ],
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql(
            "DELETE l FROM landmarks l
             INNER JOIN cities c ON c.id = l.city_id
             WHERE c.slug = 'minsk'
               AND l.slug IN ('akvapark-lebyazhij', 'akvapark-fristajl')"
        );
    }

    /**
     * @return list<array{
     *   name: string,
     *   name_genitive: string,
     *   slug: string,
     *   latitude: float,
     *   longitude: float,
     *   category: string,
     *   short_description: string,
     *   description: string,
     *   address: string,
     *   facts: list<array{label: string, value: string}>,
     *   guest_tips: list<string>,
     *   sort_order: int
     * }>
     */
    private function landmarks(): array
    {
        return [
            [
                'name' => 'Аквапарк «Лебяжий»',
                'name_genitive' => 'аквапарка «Лебяжий»',
                'slug' => 'akvapark-lebyazhij',
                'latitude' => 53.9379,
                'longitude' => 27.4698,
                'category' => 'aquapark',
                'short_description' => 'Крупнейший аквапарк Беларуси на проспекте Победителей: горки, SPA, бассейны и зона отдыха у водохранилища Дрозды.',
                'description' => '<p>Аквапарк «Лебяжий» — пятиуровневый водный комплекс площадью более 32 000 м² на пр. Победителей, 120. Здесь аквазона с горками и «ленивой рекой», SPA-центр, фитнес и кафе.</p><p>Комплекс расположен у водохранилища Дрозды и заказника «Лебяжий» — в шаге от Минск-Арены. Удобен для семейного отдыха и гостей, которые хотят жить рядом с крупным развлекательным центром на западе города.</p>',
                'address' => 'пр. Победителей, 120, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'аквапарк'],
                    ['label' => 'Площадь', 'value' => 'более 32 000 м²'],
                    ['label' => 'Рядом', 'value' => 'Минск-Арена'],
                ],
                'guest_tips' => [
                    'Билеты в выходные лучше бронировать заранее — бывает много посетителей.',
                    'У комплекса большая парковка — удобно приезжать на машине.',
                ],
                'sort_order' => 170,
            ],
            [
                'name' => 'Аквапарк «Фристайл»',
                'name_genitive' => 'аквапарка «Фристайл»',
                'slug' => 'akvapark-fristajl',
                'latitude' => 53.918532,
                'longitude' => 27.605617,
                'category' => 'aquapark',
                'short_description' => 'Аквапарк в центре Минска у метро «Академия Наук»: аквазона, банно-термальный комплекс и детская зона на ул. Сурганова.',
                'description' => '<p>Аквапарк «Фристайл» — городской водный комплекс на ул. Сурганова, 4а, в здании Дворца водного спорта. Здесь аквазона с горками и «ленивой рекой», банно-термальный комплекс, детский водный городок и SPA.</p><p>От метро «Академия Наук» — около 5 минут пешком. Удобная точка для гостей, которые хотят совместить центр города с семейным отдыхом у воды.</p>',
                'address' => 'ул. Сурганова, 4а, Минск',
                'facts' => [
                    ['label' => 'Тип', 'value' => 'аквапарк'],
                    ['label' => 'Метро', 'value' => 'Академия Наук'],
                    ['label' => 'Район', 'value' => 'Советский'],
                ],
                'guest_tips' => [
                    'От метро «Академия Наук» выходите в сторону «Дворец водного спорта».',
                    'В будни после 15:00 бывает меньше людей, чем в выходные.',
                ],
                'sort_order' => 180,
            ],
        ];
    }
}
