<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Static page "Условия использования": seed posutki.by copy, fill empty body, refresh legacy RNB.by default meta.
 */
final class Version20260604120000 extends AbstractMigration
{
    private const SLUG = 'usloviya-ispolzovaniya';

    private const LEGACY_META_TITLE = 'Условия использования — RNB.by';

    public function getDescription(): string
    {
        return 'Seed or fill static page usloviya-ispolzovaniya (posutki.by terms of use)';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'HTML'
<p>Настоящие условия определяют правила использования сайта posutki.by — сервиса посуточной аренды жилья. Регистрируясь и пользуясь сервисом, вы подтверждаете, что ознакомились с этим документом и принимаете его положения.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Предмет соглашения</h2>
<p>posutki.by предоставляет доступ к объявлениям об объектах посуточной аренды, инструментам поиска и бронирования, личному кабинету и вспомогательным материалам. Состав и доступность функций могут изменяться в целях развития сервиса.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Учётная запись</h2>
<p>Вы обязуетесь указывать достоверные данные при регистрации и поддерживать их актуальность. Вы несёте ответственность за сохранность учётных данных и за действия, совершённые с вашего аккаунта.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Объявления и контент</h2>
<p>Размещая объявления, фотографии и иные материалы, вы подтверждаете наличие прав на такую информацию и согласие с правилами модерации площадки. Запрещён контент, нарушающий законодательство Республики Беларусь или права третьих лиц.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Бронирование и сделки</h2>
<p>Условия проживания, оплаты и отмены определяются сторонами — гостем и арендодателем — на основании информации в объявлении и договорённостей между ними. posutki.by не является стороной договоров аренды и не гарантирует достижение ожидаемого результата при использовании сервиса.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">5. Ограничение ответственности</h2>
<p>Информация на сайте носит справочный характер. Мы стремимся поддерживать актуальность объявлений, однако не несём ответственности за неточности, предоставленные пользователями, и за последствия решений, принятых на их основе.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">6. Изменения и контакты</h2>
<p>Мы можем обновлять настоящие условия; актуальная редакция публикуется на этой странице. Вопросы по работе сервиса — через раздел <a href="/kontakty/">«Контакты»</a>.</p>
</div>
</div>
HTML;

        $metaTitle = 'Условия использования — posutki.by';
        $metaDescription = 'Правила использования posutki.by: учётная запись, объявления, бронирование и ответственность сторон.';

        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM static_pages WHERE slug = ?',
            [self::SLUG]
        );
        if ($count === 0) {
            $this->addSql(
                'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [self::SLUG, 'Условия использования', $content, $metaTitle, $metaDescription]
            );
        }

        $this->addSql(
            'UPDATE static_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ? AND LENGTH(TRIM(content)) = 0',
            ['Условия использования', $content, $metaTitle, $metaDescription, self::SLUG]
        );

        $this->addSql(
            'UPDATE static_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ? AND meta_title = ?',
            ['Условия использования', $content, $metaTitle, $metaDescription, self::SLUG, self::LEGACY_META_TITLE]
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('usloviya-ispolzovaniya seed/fill cannot be safely reverted.');
    }
}
