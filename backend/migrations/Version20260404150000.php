<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260404150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Seed static page: Условия использования (usloviya-ispolzovaniya)';
    }

    public function up(Schema $schema): void
    {
        $content = <<<'HTML'
<p>Настоящие условия определяют правила использования сайта RNB.by. Регистрируясь и пользуясь сервисом, вы подтверждаете, что ознакомились с этим документом и принимаете его положения.</p>
<div class="space-y-5 text-sm leading-7 text-foreground/90 sm:text-base">
<div>
<h2 class="mb-2 text-lg font-medium">1. Предмет соглашения</h2>
<p>RNB.by предоставляет доступ к информации об объявлениях о недвижимости, статьям и вспомогательным инструментам. Состав и доступность функций могут изменяться в целях развития сервиса.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">2. Учётная запись и данные пользователя</h2>
<p>Вы обязуетесь указывать достоверные данные при регистрации и поддерживать их актуальность. Вы несёте ответственность за сохранность учётных данных и за действия, совершённые с вашего аккаунта.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">3. Размещение объявлений и контента</h2>
<p>Размещая объявления и иные материалы, вы подтверждаете наличие прав на такую информацию и согласие с правилами модерации площадки. Запрещён контент, нарушающий законодательство или права третьих лиц.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">4. Ограничение ответственности</h2>
<p>Информация на сайте носит справочный характер. Условия сделок определяются сторонами самостоятельно. RNB.by не является стороной договоров между пользователями и не гарантирует достижение результата при использовании сервиса.</p>
</div>
<div>
<h2 class="mb-2 text-lg font-medium">5. Изменения и контакты</h2>
<p>Мы можем обновлять настоящие условия; актуальная редакция публикуется на этой странице. Вопросы по работе сервиса: <a href="mailto:info@rnb.by">info@rnb.by</a>.</p>
</div>
</div>
HTML;

        $this->addSql(
            'INSERT INTO static_pages (slug, title, content, meta_title, meta_description, updated_at) VALUES (?, ?, ?, ?, ?, NOW())',
            [
                'usloviya-ispolzovaniya',
                'Условия использования',
                $content,
                'Условия использования — RNB.by',
                'Правила использования сайта RNB.by: учётная запись, размещение объявлений и ответственность сторон.',
            ]
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM static_pages WHERE slug = 'usloviya-ispolzovaniya'");
    }
}
