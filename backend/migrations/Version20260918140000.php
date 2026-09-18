<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use App\Infrastructure\Migration\Data\ArendomPartnerSplitData;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Arendom currently has one account. Split it into two partners by city:
 * Юля-Саша keeps the original user with +375293197117,
 * Ольга-Диана gets a new phone-verified user with +375447076448.
 *
 * Listings in cities absent from the manager table stay on the original account.
 */
final class Version20260918140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Split Arendom partner into two accounts by city and assign manager phones';
    }

    public function up(Schema $schema): void
    {
        $sourceUserId = $this->findArendomOwnerId();
        $source = $this->connection->fetchAssociative(
            'SELECT id, phone, is_phone_verified, avatar, phone_has_viber, phone_has_whatsapp,
                    telegram, has_used_free_placement_trial, allow_guest_booking_inquiries,
                    allow_messages_and_inquiries, is_partner, partner_listing_limit,
                    is_trusted_publisher
             FROM users WHERE id = ?',
            [$sourceUserId],
        );
        $this->abortIf($source === false, sprintf('Arendom user #%d not found.', $sourceUserId));

        $this->assertPhoneAvailable(ArendomPartnerSplitData::FIRST_PHONE, $sourceUserId);
        $this->assertPhoneAvailable(ArendomPartnerSplitData::SECOND_PHONE, null);

        $secondCityIds = $this->cityIdsBySlugs(ArendomPartnerSplitData::SECOND_PARTNER_CITY_SLUGS);

        $this->connection->executeStatement(
            'UPDATE users
             SET phone = ?, is_phone_verified = 1, updated_at = NOW()
             WHERE id = ?',
            [ArendomPartnerSplitData::FIRST_PHONE, $sourceUserId],
        );

        $now = new \DateTimeImmutable();
        $this->connection->insert('users', [
            'email' => null,
            'password' => bin2hex(random_bytes(32)),
            'first_name' => ArendomPartnerSplitData::SECOND_PARTNER_FIRST_NAME,
            'last_name' => '',
            'phone' => ArendomPartnerSplitData::SECOND_PHONE,
            'avatar' => $source['avatar'],
            'created_at' => $now,
            'updated_at' => $now,
            'is_verified' => 0,
            'roles' => ['ROLE_USER'],
            'google_id' => null,
            'is_phone_verified' => 1,
            'phone_has_viber' => (int) $source['phone_has_viber'],
            'phone_has_whatsapp' => (int) $source['phone_has_whatsapp'],
            'telegram' => $source['telegram'],
            'has_used_free_placement_trial' => 1,
            'allow_guest_booking_inquiries' => (int) $source['allow_guest_booking_inquiries'],
            'allow_messages_and_inquiries' => (int) $source['allow_messages_and_inquiries'],
            'is_partner' => (int) $source['is_partner'],
            'partner_listing_limit' => $source['partner_listing_limit'],
            'is_trusted_publisher' => (int) $source['is_trusted_publisher'],
        ], [
            'created_at' => Types::DATETIME_IMMUTABLE,
            'updated_at' => Types::DATETIME_IMMUTABLE,
            'roles' => Types::JSON,
            'is_verified' => Types::INTEGER,
            'is_phone_verified' => Types::INTEGER,
            'phone_has_viber' => Types::INTEGER,
            'phone_has_whatsapp' => Types::INTEGER,
            'has_used_free_placement_trial' => Types::INTEGER,
            'allow_guest_booking_inquiries' => Types::INTEGER,
            'allow_messages_and_inquiries' => Types::INTEGER,
            'is_partner' => Types::INTEGER,
            'is_trusted_publisher' => Types::INTEGER,
        ]);

        $secondUserId = (int) $this->connection->lastInsertId();
        $this->abortIf($secondUserId <= 0, 'Failed to create the second Arendom partner user.');

        $this->connection->insert('user_phones', [
            'user_id' => $secondUserId,
            'phone' => ArendomPartnerSplitData::SECOND_PHONE,
            'is_verified' => 1,
            'code' => null,
            'code_expires_at' => null,
            'created_at' => $now,
            'has_viber' => (int) $source['phone_has_viber'],
            'has_whatsapp' => (int) $source['phone_has_whatsapp'],
        ], [
            'is_verified' => Types::INTEGER,
            'created_at' => Types::DATETIME_IMMUTABLE,
            'has_viber' => Types::INTEGER,
            'has_whatsapp' => Types::INTEGER,
        ]);

        $placeholders = implode(', ', array_fill(0, count($secondCityIds), '?'));
        $this->connection->executeStatement(
            sprintf(
                'UPDATE properties
                 SET owner_id = ?, updated_at = NOW()
                 WHERE owner_id = ? AND city_id IN (%s)',
                $placeholders,
            ),
            [$secondUserId, $sourceUserId, ...$secondCityIds],
        );

        $this->reassignRelatedOwner($sourceUserId, $secondUserId);
    }

    public function down(Schema $schema): void
    {
        $this->throwIrreversibleMigrationException(
            'Arendom partner split cannot be restored: the previous phone and listing owners are not stored.',
        );
    }

    private function findArendomOwnerId(): int
    {
        $ownerIds = $this->connection->fetchFirstColumn(
            'SELECT DISTINCT owner_id FROM properties WHERE external_source = ?',
            [ArendomPartnerSplitData::EXTERNAL_SOURCE],
        );
        $ownerIds = array_map(static fn(mixed $id): int => (int) $id, $ownerIds);

        $this->abortIf(
            $ownerIds === [],
            'No Arendom listings found (properties.external_source = arendom).',
        );
        $this->abortIf(
            count($ownerIds) !== 1,
            sprintf(
                'Expected a single Arendom owner, found user ids: %s.',
                implode(', ', $ownerIds),
            ),
        );

        return $ownerIds[0];
    }

    /**
     * @param list<string> $slugs
     * @return list<int>
     */
    private function cityIdsBySlugs(array $slugs): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, slug FROM cities WHERE slug IN (?)',
            [$slugs],
            [ArrayParameterType::STRING],
        );

        $idsBySlug = [];
        foreach ($rows as $row) {
            $idsBySlug[(string) $row['slug']] = (int) $row['id'];
        }

        $missing = array_values(array_diff($slugs, array_keys($idsBySlug)));
        $this->abortIf(
            $missing !== [],
            sprintf('Missing cities for Arendom split: %s.', implode(', ', $missing)),
        );

        return array_values($idsBySlug);
    }

    private function assertPhoneAvailable(string $phone, ?int $allowedUserId): void
    {
        $takenId = $this->connection->fetchOne(
            'SELECT id FROM users WHERE is_phone_verified = 1 AND phone = ?',
            [$phone],
        );
        if ($takenId === false || $takenId === null) {
            return;
        }

        $takenId = (int) $takenId;
        $this->abortIf(
            $allowedUserId === null || $takenId !== $allowedUserId,
            sprintf('Verified phone %s is already used by user #%d.', $phone, $takenId),
        );
    }

    private function reassignRelatedOwner(int $fromUserId, int $toUserId): void
    {
        $this->connection->executeStatement(
            'UPDATE conversations c
             INNER JOIN properties p ON p.id = c.property_id
             SET c.seller_id = p.owner_id
             WHERE c.seller_id = ? AND p.owner_id = ?',
            [$fromUserId, $toUserId],
        );
        $this->connection->executeStatement(
            'UPDATE booking_inquiries bi
             INNER JOIN properties p ON p.id = bi.property_id
             SET bi.owner_id = p.owner_id
             WHERE bi.owner_id = ? AND p.owner_id = ?',
            [$fromUserId, $toUserId],
        );
        $this->connection->executeStatement(
            'UPDATE property_placement_purchases pp
             INNER JOIN properties p ON p.id = pp.property_id
             SET pp.owner_id = p.owner_id
             WHERE pp.owner_id = ? AND p.owner_id = ?',
            [$fromUserId, $toUserId],
        );
    }
}
