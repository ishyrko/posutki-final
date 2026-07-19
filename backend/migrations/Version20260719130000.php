<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Update operator name on static page politika-obrabotki-personalnyh-dannyh.
 */
final class Version20260719130000 extends AbstractMigration
{
    private const SLUG = 'politika-obrabotki-personalnyh-dannyh';

    private const OLD_OPERATOR_PARAGRAPH = '<p>Оператором персональных данных является владелец сервиса posutki.by. По вопросам обработки данных вы можете обратиться через раздел <a href="/kontakty/">«Контакты»</a> или по адресу <a href="mailto:info@posutki.by">info@posutki.by</a>.</p>';

    private const NEW_OPERATOR_PARAGRAPH = '<p>Оператором персональных данных является <strong>ИП Ширко Иван Александрович</strong>, УНП 194017730 (далее — Оператор). По вопросам обработки данных вы можете обратиться через раздел <a href="/kontakty/">«Контакты»</a> или по адресу <a href="mailto:info@posutki.by">info@posutki.by</a>.</p>';

    public function getDescription(): string
    {
        return 'Set IP Shirko as personal data operator on politika-obrabotki-personalnyh-dannyh';
    }

    public function up(Schema $schema): void
    {
        $content = (string) $this->connection->fetchOne(
            'SELECT content FROM static_pages WHERE slug = ?',
            [self::SLUG]
        );

        if ($content === '') {
            return;
        }

        $updated = str_replace(self::OLD_OPERATOR_PARAGRAPH, self::NEW_OPERATOR_PARAGRAPH, $content);
        if ($updated === $content) {
            return;
        }

        $this->addSql(
            'UPDATE static_pages SET content = ?, updated_at = NOW() WHERE slug = ?',
            [$updated, self::SLUG]
        );
    }

    public function down(Schema $schema): void
    {
        $content = (string) $this->connection->fetchOne(
            'SELECT content FROM static_pages WHERE slug = ?',
            [self::SLUG]
        );

        if ($content === '') {
            return;
        }

        $updated = str_replace(self::NEW_OPERATOR_PARAGRAPH, self::OLD_OPERATOR_PARAGRAPH, $content);
        if ($updated === $content) {
            return;
        }

        $this->addSql(
            'UPDATE static_pages SET content = ?, updated_at = NOW() WHERE slug = ?',
            [$updated, self::SLUG]
        );
    }
}
