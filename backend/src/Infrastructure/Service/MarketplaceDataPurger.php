<?php

declare(strict_types=1);

namespace App\Infrastructure\Service;

use Doctrine\DBAL\Connection;

/**
 * Удаляет пользователей и объявления с зависимыми строками.
 * Справочники (регионы, города, улицы, метро), статьи, статические страницы,
 * курсы валют и обращения с формы контактов не затрагиваются.
 */
final class MarketplaceDataPurger
{
    private const PROPERTY_FK = [
        ['property_revisions', 'FK_PROPERTY_REVISIONS_PROPERTY'],
        ['property_metro_stations', 'FK_PROPERTY_METRO_PROPERTY'],
    ];

    /** @var list<string> */
    private const PROPERTY_TABLES = [
        'messages',
        'conversations',
        'favorites',
        'reviews',
        'property_daily_stats',
        'property_metro_stations',
        'property_revisions',
        'properties',
    ];

    /** @var list<string> */
    private const USER_TABLES = [
        'user_individual_profiles',
        'user_business_profiles',
        'user_phones',
        'auth_phone_codes',
        'sms_send_log',
        'users',
    ];

    public function purgeProperties(Connection $conn): void
    {
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $this->dropPropertyForeignKeys($conn);

        foreach (self::PROPERTY_TABLES as $table) {
            $conn->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }

        $this->restorePropertyForeignKeys($conn);
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    public function purgeUsersAndProperties(Connection $conn): void
    {
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=0');
        $this->dropPropertyForeignKeys($conn);

        foreach ([...self::PROPERTY_TABLES, ...self::USER_TABLES] as $table) {
            $conn->executeStatement(sprintf('TRUNCATE TABLE %s', $table));
        }

        $this->restorePropertyForeignKeys($conn);
        $conn->executeStatement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function dropPropertyForeignKeys(Connection $conn): void
    {
        foreach (self::PROPERTY_FK as [$table, $constraint]) {
            if ($this->foreignKeyExists($conn, $table, $constraint)) {
                $conn->executeStatement(sprintf(
                    'ALTER TABLE %s DROP FOREIGN KEY %s',
                    $table,
                    $constraint,
                ));
            }
        }
    }

    private function restorePropertyForeignKeys(Connection $conn): void
    {
        $this->addForeignKeyIfMissing(
            $conn,
            'property_revisions',
            'FK_PROPERTY_REVISIONS_PROPERTY',
            'ALTER TABLE property_revisions ADD CONSTRAINT FK_PROPERTY_REVISIONS_PROPERTY FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE',
        );
        $this->addForeignKeyIfMissing(
            $conn,
            'property_metro_stations',
            'FK_PROPERTY_METRO_PROPERTY',
            'ALTER TABLE property_metro_stations ADD CONSTRAINT FK_PROPERTY_METRO_PROPERTY FOREIGN KEY (property_id) REFERENCES properties (id) ON DELETE CASCADE',
        );
    }

    private function foreignKeyExists(Connection $conn, string $table, string $constraint): bool
    {
        return (int) $conn->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?',
            [$table, $constraint],
        ) > 0;
    }

    private function addForeignKeyIfMissing(
        Connection $conn,
        string $table,
        string $constraint,
        string $sql,
    ): void {
        if (!$this->foreignKeyExists($conn, $table, $constraint)) {
            $conn->executeStatement($sql);
        }
    }
}
