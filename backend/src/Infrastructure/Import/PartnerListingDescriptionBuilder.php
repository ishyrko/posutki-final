<?php

declare(strict_types=1);

namespace App\Infrastructure\Import;

use App\Domain\Property\Entity\City;

final class PartnerListingDescriptionBuilder
{
    public const TEMPLATE_REPEAT_THRESHOLD = 3;

    private const GUEST_SENTENCES = [
        'Подойдёт для компании до %d гостей.',
        'Размещение до %d гостей.',
        'Рассчитана на %d гостей.',
    ];

    public function __construct(
        private readonly PartnerListingTitleBuilder $titleBuilder,
    ) {
    }

    public function build(
        ?int $rooms,
        ?int $guests,
        City $city,
        ?string $streetLabel,
        string $building,
        string $externalId,
        bool $hasBusinessDocs,
        int $minStayDays,
        string $checkInTime,
        string $checkOutTime,
        ?string $metroStation = null,
    ): string {
        $sentences = [
            $this->leadSentence($rooms, $city, $streetLabel, $building),
        ];

        if ($guests !== null && $guests > 0) {
            $sentences[] = $this->guestSentence($guests, $externalId);
        }
        if ($metroStation !== null && trim($metroStation) !== '') {
            $sentences[] = $this->metroSentence($metroStation);
        }
        if ($hasBusinessDocs) {
            $sentences[] = 'Предоставляются отчётные документы для командированных.';
        }
        $sentences[] = sprintf(
            'Бронирование от %d суток, заезд с %s, выезд до %s.',
            max(1, $minStayDays),
            $checkInTime,
            $checkOutTime,
        );

        return implode(' ', $sentences);
    }

    public function appendMetro(string $description, string $stationName): string
    {
        $stationName = trim($stationName);
        $description = trim($description);
        if ($stationName === '' || $description === '') {
            return $description;
        }
        if (str_contains(mb_strtolower($description), 'станция метро')) {
            return $description;
        }

        $metro = $this->metroSentence($stationName);
        $bookingNeedle = 'Бронирование от';
        $bookingPos = mb_strpos($description, $bookingNeedle);
        if ($bookingPos !== false) {
            $before = rtrim(mb_substr($description, 0, $bookingPos));
            $after = ltrim(mb_substr($description, $bookingPos));

            return $before . ' ' . $metro . ' ' . $after;
        }

        return $description . ' ' . $metro;
    }

    public static function fingerprint(string $text): string
    {
        $folded = mb_strtolower(str_replace(['ё', 'Ё'], ['е', 'Е'], $text));
        $folded = preg_replace('/по адресу\s+.*?(?=\s+предоставление|\s*$)/u', 'по адресу', $folded) ?? $folded;
        $stripped = preg_replace('/[\d\p{P}\p{S}]+/u', ' ', $folded) ?? $folded;

        return trim(preg_replace('/\s+/u', ' ', $stripped) ?? $stripped);
    }

    /**
     * @param list<mixed> $rows
     *
     * @return array<string, int>
     */
    public static function fingerprintCountsFromRows(array $rows): array
    {
        $counts = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $description = trim((string) ($row['description'] ?? ''));
            if ($description === '') {
                continue;
            }
            $fingerprint = self::fingerprint($description);
            if ($fingerprint === '') {
                continue;
            }
            $counts[$fingerprint] = ($counts[$fingerprint] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * @param array<string, int> $fingerprintCounts
     */
    public static function isTemplate(string $description, array $fingerprintCounts): bool
    {
        $description = trim($description);
        if ($description === '') {
            return true;
        }
        $fingerprint = self::fingerprint($description);
        if ($fingerprint === '') {
            return true;
        }

        return ($fingerprintCounts[$fingerprint] ?? 0) >= self::TEMPLATE_REPEAT_THRESHOLD;
    }

    public static function mentionsBusinessDocs(string $text): bool
    {
        return preg_match('/отч[её]тн|командирован/iu', $text) === 1;
    }

    private function leadSentence(?int $rooms, City $city, ?string $streetLabel, string $building): string
    {
        $type = $this->roomsPhrase($rooms) . ' на сутки';
        $parts = [$type . ' ' . $this->cityPhrase($city)];

        if ($streetLabel !== null && trim($streetLabel) !== '') {
            $formatted = $this->titleBuilder->formatStreetLabel($streetLabel);
            if ($formatted !== '') {
                $parts[] = $formatted;
            }
        }

        $building = trim($building);
        if ($building !== '') {
            $parts[] = 'д. ' . $building;
        }

        return implode(', ', $parts) . '.';
    }

    private function guestSentence(int $guests, string $externalId): string
    {
        $index = ((int) sprintf('%u', crc32($externalId))) % count(self::GUEST_SENTENCES);

        return sprintf(self::GUEST_SENTENCES[$index], $guests);
    }

    private function metroSentence(string $stationName): string
    {
        return sprintf('Рядом станция метро %s.', trim($stationName));
    }

    private function roomsPhrase(?int $rooms): string
    {
        if ($rooms === null || $rooms < 1 || $rooms > 6) {
            return 'Квартира';
        }

        return sprintf('%d-комнатная квартира', $rooms);
    }

    private function cityPhrase(City $city): string
    {
        $prepositional = $city->getNamePrepositional();
        if ($prepositional !== null && trim($prepositional) !== '') {
            return 'в ' . trim($prepositional);
        }

        $fallback = trim($city->getShortName()) !== ''
            ? $city->getShortName()
            : $city->getName();

        return 'в г. ' . $fallback;
    }
}
