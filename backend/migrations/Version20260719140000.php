<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Transform static page from personal data policy to consent (like kvartirka.by/processing).
 */
final class Version20260719140000 extends AbstractMigration
{
    private const OLD_SLUG = 'politika-obrabotki-personalnyh-dannyh';

    private const NEW_SLUG = 'soglasie-na-obrabotku-personalnyh-dannyh';

    public function getDescription(): string
    {
        return 'Transform politika-obrabotki-personalnyh-dannyh into soglasie-na-obrabotku-personalnyh-dannyh';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'HTML'
<p class="text-sm text-muted-foreground">Дата последнего обновления: 19 июля 2026 г.</p>
<p>Я, субъект персональных данных, действуя свободно, своей волей и в своём интересе, даю согласие <strong>ИП Ширко Иван Александрович</strong> (Оператор, УНП 194017730) на обработку моих персональных данных в соответствии с Законом РБ №99-З.</p>
<p>Использование Сайта posutki.by, регистрация, размещение объявления или проставление чекбокса/активного действия на сайте подтверждает согласие с условиями настоящего документа.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Перечень данных</h2>
<ul class="list-disc space-y-1 pl-5">
<li>ФИО, телефон, email</li>
<li>данные объявлений</li>
<li>технические данные, включая данные аналитических сервисов (Яндекс Метрика)</li>
<li>иные добровольно предоставленные сведения</li>
</ul>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Цели обработки</h2>
<ul class="list-disc space-y-1 pl-5">
<li>регистрация и идентификация</li>
<li>предоставление функционала и услуг сайта</li>
<li>размещение, модерация и администрирование объявлений</li>
<li>связь с пользователем</li>
<li>улучшение работы, безопасности и аналитика сайта</li>
<li>исполнение требований законодательства</li>
</ul>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Действия с данными</h2>
<p>сбор, запись, систематизация, хранение, изменение, использование, обезличивание, блокирование, удаление</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Передача третьим лицам</h2>
<ul class="list-disc space-y-1 pl-5">
<li>хостинг, техподдержка, SMS/email-сервисы, платёжные сервисы</li>
<li>только в пределах закона с обеспечением конфиденциальности</li>
</ul>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">5. Срок действия и отзыв</h2>
<ul class="list-disc space-y-1 pl-5">
<li>действует до момента отзыва согласия</li>
<li>отзыв возможен по телефону, email или через раздел <a href="/kontakty/">«Контакты»</a></li>
<li>отзыв не влияет на законность обработки до момента его получения</li>
<li>использование сайта после отзыва может быть ограничено</li>
</ul>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">6. Подтверждение согласия</h2>
<ul class="list-disc space-y-1 pl-5">
<li>чекбокс или иное активное действие на сайте подтверждает согласие</li>
<li>пользователь подтверждает, что ознакомлен с <a href="/politika-konfidentsialnosti/">политикой конфиденциальности</a></li>
</ul>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">7. Контакты</h2>
<p>
<strong>ИП Ширко Иван Александрович</strong>, УНП 194017730<br>
Тел.: <a href="tel:+375333556699">+375 (33) 355-66-99</a><br>
Email: <a href="mailto:info@posutki.by">info@posutki.by</a>
</p>
</div>
</div>
HTML;

        $metaTitle = 'Согласие на обработку персональных данных — posutki.by';
        $metaDescription = 'Согласие субъекта персональных данных на обработку данных при использовании posutki.by.';

        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM static_pages WHERE slug = ?',
            [self::OLD_SLUG]
        );

        if ($count > 0) {
            $this->addSql(
                'UPDATE static_pages SET slug = ?, title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ?',
                [
                    self::NEW_SLUG,
                    'Согласие на обработку персональных данных',
                    $content,
                    $metaTitle,
                    $metaDescription,
                    self::OLD_SLUG,
                ]
            );

            return;
        }

        $newCount = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM static_pages WHERE slug = ?',
            [self::NEW_SLUG]
        );

        if ($newCount === 0) {
            $this->addSql(
                'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [
                    self::NEW_SLUG,
                    'Согласие на обработку персональных данных',
                    $content,
                    $metaTitle,
                    $metaDescription,
                ]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Consent page transform cannot be safely reverted.');
    }
}
