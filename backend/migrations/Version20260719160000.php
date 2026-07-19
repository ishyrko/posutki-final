<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add web analytics disclosure to politika-konfidentsialnosti.
 */
final class Version20260719160000 extends AbstractMigration
{
    private const SLUG = 'politika-konfidentsialnosti';

    public function getDescription(): string
    {
        return 'Add analytics section to politika-konfidentsialnosti static page';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'HTML'
<p>Настоящая политика описывает, какие данные обрабатывает posutki.by при использовании сервиса посуточной аренды жилья, в каких целях они используются и как мы обеспечиваем их защиту.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Какие данные мы собираем</h2>
<p>Мы можем обрабатывать данные, которые вы указываете при регистрации, размещении объявлений, бронировании и обращении в поддержку: контактные данные, сведения об объекте размещения, переписку по сделкам.</p>
<p>Также автоматически могут собираться технические сведения: IP-адрес, тип браузера и устройства, язык интерфейса, сведения о сессии, файлы cookie и обезличенная статистика посещений, в том числе через сервисы веб-аналитики (Яндекс Метрика, Google Analytics).</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Для чего мы используем данные</h2>
<p>Данные используются для предоставления функций сервиса, модерации объявлений, связи с пользователями, улучшения интерфейса и качества поддержки, анализа посещаемости и поведения на Сайте, повышения безопасности, а также для выполнения требований законодательства Республики Беларусь.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Cookie и веб-аналитика</h2>
<p>Файлы cookie помогают сохранять настройки (например, регион или валюту отображения), обеспечивать вход в аккаунт и корректную работу сайта.</p>
<p>Для оценки эффективности сервиса мы используем инструменты веб-аналитики — в частности Яндекс Метрику и Google Analytics. Они могут фиксировать просмотры страниц, источники перехода, действия на сайте и технические параметры устройства. Эти сведения обрабатываются в обезличенном или псевдонимизированном виде и не используются для показа рекламы третьих лиц на основании вашего профиля на posutki.by.</p>
<p>Вы можете ограничить или отключить cookie в настройках браузера, а также воспользоваться средствами отказа, предлагаемыми поставщиками аналитических сервисов. При этом отдельные функции Сайта могут работать неполноценно.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Контакты</h2>
<p>По вопросам обработки персональных данных вы можете связаться с нами через раздел <a href="/kontakty/">«Контакты»</a>.</p>
</div>
</div>
HTML;

        $metaDescription = 'Условия обработки персональных данных, cookie и веб-аналитики на posutki.by.';

        $this->addSql(
            'UPDATE static_pages SET content = ?, meta_description = ?, updated_at = NOW() WHERE slug = ?',
            [$content, $metaDescription, self::SLUG]
        );
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Privacy policy analytics update cannot be safely reverted.');
    }
}
