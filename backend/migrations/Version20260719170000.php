<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Seed owner-facing static pages: RealtyCalendar integration and payment/refunds.
 */
final class Version20260719170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed static pages integratsiya-s-realty-calendar and oplata';
    }

    public function up(Schema $schema): void
    {
        $this->upsertPage(
            'integratsiya-s-realty-calendar',
            'Интеграция с RealtyCalendar',
            'Интеграция с RealtyCalendar — posutki.by',
            'Как синхронизировать календарь занятости на posutki.by с RealtyCalendar через iCal, чтобы избежать овербукинга.',
            <<<'HTML'
<p class="text-sm text-muted-foreground">Дата последнего обновления: 19 июля 2026 г.</p>
<p>Если вы ведёте объекты в <strong>RealtyCalendar</strong>, календарь занятости на posutki.by можно связать с каналом через формат <strong>iCal (ICS)</strong>. Так даты броней и закрытий подтягиваются автоматически и снижается риск овербукинга при размещении на нескольких площадках.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Что синхронизируется</h2>
<ul class="list-disc space-y-1 pl-5">
<li><strong>Импорт</strong> — даты занятости из RealtyCalendar (и других ICS-источников) отображаются в календаре объявления на posutki.by</li>
<li><strong>Экспорт</strong> — постоянная ICS-ссылка из личного кабинета posutki.by передаёт занятость обратно в RealtyCalendar и на другие площадки</li>
</ul>
<p>Синхронизация двусторонняя: достаточно обменяться ссылками экспорта и импорта.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Настройка со стороны posutki.by</h2>
<ol class="list-decimal space-y-2 pl-5">
<li>Войдите в <a href="/kabinet/">личный кабинет</a> и откройте нужное объявление.</li>
<li>В разделе <strong>«Календарь»</strong> скопируйте ссылку <strong>«Экспорт календаря»</strong> — её нужно будет указать в RealtyCalendar как импорт.</li>
<li>Откройте <strong>редактирование объявления</strong> → блок <strong>«Синхронизация календарей»</strong> и вставьте ICS-ссылку экспорта из RealtyCalendar (можно добавить несколько внешних календарей).</li>
<li>Сохраните изменения. Статус последнего импорта видно на странице календаря объявления.</li>
</ol>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Настройка в RealtyCalendar</h2>
<ol class="list-decimal space-y-2 pl-5">
<li>В кабинете RealtyCalendar откройте <strong>«Менеджер каналов»</strong>.</li>
<li>Напротив нужного объекта нажмите «+» и выберите <strong>iCalendar</strong>.</li>
<li>В поле <strong>«Экспорт»</strong> скопируйте ссылку — вставьте её в блок синхронизации календарей на posutki.by (шаг выше).</li>
<li>В поле <strong>«Импорт»</strong> вставьте ссылку экспорта из календаря объявления на posutki.by, укажите название (например, «posutki.by») и сохраните.</li>
</ol>
<p>Подробные скриншоты интерфейса RealtyCalendar смотрите в их справке по подключению iCalendar.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Важные замечания</h2>
<ul class="list-disc space-y-1 pl-5">
<li>Обновление по iCal происходит не мгновенно: обычно от нескольких минут до нескольких часов в зависимости от стороны, которая запрашивает календарь.</li>
<li>После тестовой брони в RealtyCalendar проверьте, что даты закрылись в календаре на posutki.by, и наоборот.</li>
<li>Ответственность за актуальность закрытых дат при синхронизации через iCal лежит на владельце объекта.</li>
<li>Синхронизация доступна для объявлений с посуточной арендой.</li>
</ul>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">5. Помощь</h2>
<p>Если ссылки не обновляются или даты расходятся — напишите нам через <a href="/kontakty/">«Контакты»</a> или на <a href="mailto:info@posutki.by">info@posutki.by</a>, приложите ID объявления и ICS-ссылки.</p>
</div>
</div>
HTML
        );

        $this->upsertPage(
            'oplata',
            'Оплата',
            'Оплата услуг — posutki.by',
            'Процесс оплаты VIP-размещения на posutki.by, способы оплаты банковской картой через bePaid и условия возврата.',
            <<<'HTML'
<p class="text-sm text-muted-foreground">Дата последнего обновления: 19 июля 2026 г.</p>
<p>На этой странице описаны оплата услуг сайта posutki.by (платное VIP-размещение и связанные опции), доступные способы расчёта и порядок возврата. Все платежи принимаются только в белорусских рублях (BYN).</p>
<p><strong>Важно:</strong> posutki.by не принимает оплату за проживание и не является посредником в расчётах между гостем и владельцем жилья. Условия предоплаты, залога и окончательного расчёта за аренду гости и хозяева согласовывают напрямую.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Процесс оплаты услуг сайта</h2>
<ol class="list-decimal space-y-2 pl-5">
<li>Выберите тариф или опцию в <a href="/tarify/">разделе тарифов</a> либо в личном кабинете у нужного объявления.</li>
<li>Оформите заказ (создание заявки на размещение / буст) — откроется страница оплаты.</li>
<li>Нажмите кнопку перехода к оплате: вы попадёте на защищённую платёжную страницу процессинга <strong>bePaid</strong>.</li>
<li>Укажите данные карты и подтвердите платёж. Если карта поддерживает 3‑D Secure, банк запросит дополнительную проверку владельца карты.</li>
<li>После успешной оплаты услуга активируется автоматически; подтверждение придёт на email, указанный в аккаунте. Сохраняйте данные об оплате.</li>
</ol>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Способ оплаты</h2>
<p>Оплата банковской картой онлайн через систему электронных платежей <strong>bePaid</strong>. На платёжной странице отображаются номер заказа и сумма.</p>
<p>Принимаются карты: Visa, Visa Electron, Mastercard, Maestro, Белкарт, а также оплата через Google Pay (если доступна на устройстве).</p>
<p>Платёжная страница bePaid соответствует требованиям безопасности передачи данных (PCI DSS Level 1). Конфиденциальные данные карты обрабатываются в зашифрованном виде; доступ к авторизационным страницам выполняется по протоколу SSL/TLS.</p>
<p>Оплата через систему «Расчёт» (ЕРИП) на posutki.by <strong>не предоставляется</strong>.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Возврат денежных средств</h2>
<p>Если услуга сайта оказалась недоступна или не соответствует описанию, обратитесь в поддержку: укажите услугу (номер заказа / ID объявления) и причину обращения.</p>
<p>
Тел.: <a href="tel:+375333556699">+375 (33) 355-66-99</a><br>
Email: <a href="mailto:info@posutki.by">info@posutki.by</a><br>
Также можно написать через раздел <a href="/kontakty/">«Контакты»</a>.
</p>
<p>Возврат по оплате картой через сайт выполняется на ту же карту, с которой был платёж. Срок зачисления — обычно от 3 до 30 банковских дней с момента оформления возврата (зависит от банка-эмитента).</p>
<p>Возврат не производится, если услуга уже оказана (размещение или буст активированы и использованы по назначению), а также при нарушении условий <a href="/publichnaya-oferta/">публичной оферты</a>, если иное не предусмотрено законодательством Республики Беларусь.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Реквизиты</h2>
<p>
<strong>ИП Ширко Иван Александрович</strong>, УНП 194017730<br>
г. Минск, ул. Кальварийская, 4, кв. 101<br>
Тел.: <a href="tel:+375333556699">+375 (33) 355-66-99</a><br>
Email: <a href="mailto:info@posutki.by">info@posutki.by</a>
</p>
</div>
</div>
HTML
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM static_pages WHERE slug IN (?, ?)", [
            'integratsiya-s-realty-calendar',
            'oplata',
        ]);
    }

    /**
     * @param non-empty-string $slug
     * @param non-empty-string $title
     * @param non-empty-string $metaTitle
     * @param non-empty-string $metaDescription
     * @param non-empty-string $content
     */
    private function upsertPage(
        string $slug,
        string $title,
        string $metaTitle,
        string $metaDescription,
        string $content,
    ): void {
        $count = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM static_pages WHERE slug = ?',
            [$slug]
        );

        if ($count === 0) {
            $this->addSql(
                'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
                [$slug, $title, $content, $metaTitle, $metaDescription]
            );

            return;
        }

        $this->addSql(
            'UPDATE static_pages SET title = ?, content = ?, meta_title = ?, meta_description = ?, updated_at = NOW() WHERE slug = ?',
            [$title, $content, $metaTitle, $metaDescription, $slug]
        );
    }
}
