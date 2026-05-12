<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Replaces default "О нас" seed copy still branded as RNB.by with posutki.by (посуточная аренда).
 */
final class Version20260512120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Refresh static page o-nas default content from RNB.by to posutki.by';
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

        $this->addSql(
            'UPDATE static_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ? AND meta_title = ?',
            [
                'О нас',
                $content,
                'О нас — posutki.by',
                'posutki.by — посуточная аренда квартир и домов в Беларуси: поиск жилья для гостей и размещение объявлений для собственников.',
                'o-nas',
                'О нас — RNB.by',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Default marketing copy refresh for o-nas cannot be safely reverted.');
    }
}
