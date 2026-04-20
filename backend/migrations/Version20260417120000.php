<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Spread article timestamps randomly across the last 10 days (dev/demo data).
 */
final class Version20260417120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Randomize article created_at, updated_at, published_at within the last 10 days';
    }

    public function up(Schema $schema): void
    {
        $conn = $this->connection;
        $rows = $conn->fetchAllAssociative('SELECT id, status FROM articles');

        $now = new \DateTimeImmutable();
        $start = $now->modify('-10 days');
        $span = $now->getTimestamp() - $start->getTimestamp();
        if ($span < 1) {
            $span = 1;
        }

        foreach ($rows as $row) {
            $id = $row['id'];
            $status = (string) $row['status'];

            $createdTs = $start->getTimestamp() + random_int(0, $span);
            $tail = max(0, $now->getTimestamp() - $createdTs);
            $updatedTs = $createdTs + ($tail > 0 ? random_int(0, $tail) : 0);

            $createdStr = (new \DateTimeImmutable())->setTimestamp($createdTs)->format('Y-m-d H:i:s');
            $updatedStr = (new \DateTimeImmutable())->setTimestamp($updatedTs)->format('Y-m-d H:i:s');

            if ($status === 'published') {
                $publishedTs = random_int($createdTs, $updatedTs);
                $publishedStr = (new \DateTimeImmutable())->setTimestamp($publishedTs)->format('Y-m-d H:i:s');
                $conn->executeStatement(
                    'UPDATE articles SET created_at = ?, updated_at = ?, published_at = ? WHERE id = ?',
                    [$createdStr, $updatedStr, $publishedStr, $id]
                );
            } else {
                $conn->executeStatement(
                    'UPDATE articles SET created_at = ?, updated_at = ?, published_at = NULL WHERE id = ?',
                    [$createdStr, $updatedStr, $id]
                );
            }
        }
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException('Original article timestamps were not stored.');
    }
}
