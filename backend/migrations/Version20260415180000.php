<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Application\Service\ArticleTextSanitizer;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Apply ArticleTextSanitizer rules to existing article rows (same logic as on save).
 */
final class Version20260415180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize article title, content, excerpt (AI-typography cleanup for existing rows)';
    }

    public function up(Schema $schema): void
    {
        $sanitizer = new ArticleTextSanitizer();
        $conn = $this->connection;
        $rows = $conn->fetchAllAssociative('SELECT id, title, content, excerpt FROM articles');

        foreach ($rows as $row) {
            $id = $row['id'];
            $oldTitle = (string) $row['title'];
            $oldContent = (string) $row['content'];
            $oldExcerpt = (string) $row['excerpt'];

            $newTitle = $sanitizer->sanitizePlainText($oldTitle);
            $newContent = $sanitizer->sanitizeHtml($oldContent);
            $newExcerpt = $sanitizer->sanitizePlainText($oldExcerpt);

            if ($newTitle === $oldTitle && $newContent === $oldContent && $newExcerpt === $oldExcerpt) {
                continue;
            }

            $conn->executeStatement(
                'UPDATE articles SET title = ?, content = ?, excerpt = ? WHERE id = ?',
                [$newTitle, $newContent, $newExcerpt, $id]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Cannot restore original typography after ArticleTextSanitizer migration.');
    }
}
