<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed static page: О нас (o-nas)';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'HTML'
<p>RNB.by — информационный портал о недвижимости в Беларуси. Мы помогаем людям находить квартиры, дома и коммерческие объекты, ориентироваться на рынке и принимать взвешенные решения.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Кто мы</h2>
<p>Команда RNB.by объединяет специалистов в области недвижимости, аналитики и цифровых продуктов. Наша цель — сделать поиск жилья и коммерческой площади понятным и удобным на каждом этапе — от первого запроса до контакта с продавцом или арендодателем.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Что мы делаем</h2>
<p>На сайте представлены объявления о продаже и аренде недвижимости по городам Беларуси, фильтры и подборки по типу объекта, району и другим параметрам. Мы публикуем статьи и гиды о сделках, ипотеке и особенностях рынка, а также предлагаем инструменты вроде ипотечного калькулятора.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Для кого сервис</h2>
<p>Сервис ориентирован на частных лиц и профессиональных участников рынка: собственников, покупателей, арендаторов и агентства. Мы стремимся поддерживать актуальность информации и прозрачные правила размещения объявлений в соответствии с законодательством и нашими правилами площадки.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Связь с нами</h2>
<p>Если у вас есть вопросы о работе портала, сотрудничестве или размещении материалов, напишите нам на <a href="mailto:info@rnb.by">info@rnb.by</a> или воспользуйтесь разделом <a href="/kontakty/">«Контакты»</a>.</p>
</div>
</div>
HTML;

        $this->addSql(
            'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [
                'o-nas',
                'О нас',
                $content,
                'О нас — RNB.by',
                'RNB.by — портал недвижимости в Беларуси: объявления, статьи и инструменты для покупателей, арендаторов и собственников.',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM static_pages WHERE slug = 'o-nas'");
    }
}
