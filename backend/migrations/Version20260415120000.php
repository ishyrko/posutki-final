<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * EasyAdmin ImageField stores only the filename under upload_dir; legacy rows used full /uploads/articles/... paths,
 * which broke StringToFileTransformer on edit and cleared the cover on save.
 */
final class Version20260415120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store article cover_image as upload-relative path (filename under uploads/articles)';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $rows = $conn->fetchAllAssociative('SELECT id, cover_image FROM articles WHERE cover_image IS NOT NULL');
        foreach ($rows as $row) {
            $v = $row['cover_image'];
            if (!\is_string($v) || $v === '') {
                continue;
            }

            if (preg_match('#^https?://#i', $v) === 1) {
                continue;
            }

            $new = $this->migrateCoverToRelative($v);
            if ($new !== $v) {
                $conn->executeStatement('UPDATE articles SET cover_image = ? WHERE id = ?', [$new, $row['id']]);
            }
        }
    }

    public function down(Schema $schema): void
    {
        $conn = $this->connection;
        $rows = $conn->fetchAllAssociative('SELECT id, cover_image FROM articles WHERE cover_image IS NOT NULL');
        foreach ($rows as $row) {
            $v = $row['cover_image'];
            if (!\is_string($v) || $v === '') {
                continue;
            }

            if (preg_match('#^https?://#i', $v) === 1) {
                continue;
            }

            if (str_starts_with($v, '/uploads/')) {
                continue;
            }

            $new = '/uploads/articles/' . ltrim($v, '/');
            $conn->executeStatement('UPDATE articles SET cover_image = ? WHERE id = ?', [$new, $row['id']]);
        }
    }

    private function migrateCoverToRelative(string $coverImage): string
    {
        if (str_starts_with($coverImage, '/uploads/articles/')) {
            return substr($coverImage, strlen('/uploads/articles/'));
        }

        if (preg_match('#^/uploads/[^/]+$#', $coverImage) === 1) {
            return basename($coverImage);
        }

        return $coverImage;
    }
}
