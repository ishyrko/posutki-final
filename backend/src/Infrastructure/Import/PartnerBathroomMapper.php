<?php

declare(strict_types=1);

namespace App\Infrastructure\Import;

final class PartnerBathroomMapper
{
    /**
     * @param array<string, mixed> $row
     * @param list<string> $amenityIds
     *
     * @return array{bathrooms: int, amenities: list<string>}
     */
    public function apply(array $row, array $amenityIds): array
    {
        $amenities = [];
        foreach ($amenityIds as $id) {
            if ($id !== 'bathroom_separate' && $id !== 'bathroom_combined') {
                $amenities[] = $id;
            }
        }

        $type = $this->resolveType($row, $amenityIds);

        return match ($type) {
            'two' => [
                'bathrooms' => max(2, (int) ($row['bathrooms'] ?? 2)),
                'amenities' => $amenities,
            ],
            'separate' => [
                'bathrooms' => 1,
                'amenities' => [...$amenities, 'bathroom_separate'],
            ],
            'combined' => [
                'bathrooms' => 1,
                'amenities' => [...$amenities, 'bathroom_combined'],
            ],
            default => [
                'bathrooms' => 1,
                'amenities' => $amenities,
            ],
        };
    }

    /**
     * @param array<string, mixed> $row
     * @param list<string> $amenityIds
     */
    private function resolveType(array $row, array $amenityIds): string
    {
        $explicit = mb_strtolower(trim((string) ($row['bathroomType'] ?? '')));
        if (in_array($explicit, ['separate', 'combined', 'two'], true)) {
            return $explicit;
        }

        if (in_array('bathroom_separate', $amenityIds, true)) {
            return 'separate';
        }
        if (in_array('bathroom_combined', $amenityIds, true)) {
            return 'combined';
        }

        // Старый скрейпер arendom кодировал «Санузел: Раздельный» как bathrooms=2.
        if (isset($row['bathrooms']) && (int) $row['bathrooms'] === 2) {
            return 'separate';
        }
        if (isset($row['bathrooms']) && (int) $row['bathrooms'] > 2) {
            return 'two';
        }

        return 'combined';
    }
}
