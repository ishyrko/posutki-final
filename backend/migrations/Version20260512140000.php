<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ensures static page "О нас" (slug o-nas) exists with default HTML: INSERT if missing, UPDATE if content is empty.
 */
final class Version20260512140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed or fill static page o-nas with default HTML content when missing or empty';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'HTML'
<p>posutki.by — сервис поиска квартир, домов и гостевого жилья посуточно в Беларуси. Мы помогаем гостям быстро найти удобное размещение на короткий срок, а собственникам — показать объект заинтересованной аудитории.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Кто мы</h2>
<p>Команда posutki.by объединяет специалистов по продукту, поддержке и контенту. Наша цель — сделать бронирование и выбор жилья понятными на каждом этапе: от фильтров и карточки объекта до связи с владельцем.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Что мы делаем</h2>
<p>На сайте размещаются объявления о посуточной аренде квартир и домов по городам Беларуси, удобные фильтры по вместимости, удобствам и локации, а также материалы, которые помогают сориентироваться в сервисе и правилах площадки.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Для кого сервис</h2>
<p>Сервис ориентирован на гостей и путешественников, семьи и компании, а также на собственников и менеджеров объектов. Мы поддерживаем прозрачные правила размещения и модерацию объявлений в соответствии с законодательством и условиями использования сайта.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Связь с нами</h2>
<p>Если у вас есть вопросы о работе портала, сотрудничестве или размещении материалов, воспользуйтесь разделом <a href="/kontakty/">«Контакты»</a>.</p>
</div>
</div>
HTML;

        $metaTitle = 'О нас — posutki.by';
        $metaDescription = 'posutki.by — посуточная аренда квартир и домов в Беларуси: поиск жилья для гостей и размещение объявлений для собственников.';

        $count = (int) $this->connection->fetchOne("SELECT COUNT(*) FROM static_pages WHERE slug = 'o-nas'");
        if ($count === 0) {
            $this->addSql(
                'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
                ['o-nas', 'О нас', $content, $metaTitle, $metaDescription]
            );
        }

        $this->addSql(
            'UPDATE static_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ? AND LENGTH(TRIM(content)) = 0',
            ['О нас', $content, $metaTitle, $metaDescription, 'o-nas']
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('o-nas seed/fill cannot be safely reverted.');
    }
}
