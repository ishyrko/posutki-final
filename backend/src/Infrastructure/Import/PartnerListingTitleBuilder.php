<?php

declare(strict_types=1);

namespace App\Infrastructure\Import;

use App\Domain\Property\Entity\City;

final class PartnerListingTitleBuilder
{
    public const MAX_LENGTH = 200;

    /** @var array<string, string> trailing token => prefix */
    private const STREET_SUFFIXES = [
        'ул.' => 'ул.',
        'ул' => 'ул.',
        'пер.' => 'пер.',
        'пер' => 'пер.',
        'пр-т' => 'пр-т',
        'пр-т.' => 'пр-т',
        'пл.' => 'пл.',
        'пл' => 'пл.',
        'пр-д' => 'пр-д',
        'пр-д.' => 'пр-д',
        'м-н' => 'м-н',
        'м-н.' => 'м-н',
        'б-р' => 'б-р',
        'б-р.' => 'б-р',
        'кв-л' => 'кв-л',
        'кв-л.' => 'кв-л',
    ];

    public function build(
        ?int $rooms,
        ?int $guests,
        City $city,
        ?string $streetLabel,
        bool $forceStreet = false,
    ): string {
        $title = $this->roomsPhrase($rooms);
        $knownGuests = $guests !== null && $guests > 0;
        if ($knownGuests) {
            $title .= ' для ' . $guests . ' ' . ($guests === 1 ? 'гостя' : 'гостей');
        }
        $title .= ' ' . $this->cityPhrase($city);

        $includeStreet = ($forceStreet || !$knownGuests)
            && $streetLabel !== null
            && trim($streetLabel) !== '';
        if ($includeStreet) {
            $formatted = $this->formatStreetLabel($streetLabel);
            if ($formatted !== '') {
                $title .= ', ' . $formatted;
            }
        }

        if (mb_strlen($title) > self::MAX_LENGTH) {
            return mb_substr($title, 0, self::MAX_LENGTH);
        }

        return $title;
    }

    public function formatStreetLabel(string $label): string
    {
        $label = trim($label);
        if ($label === '') {
            return '';
        }

        if (preg_match('/^(.*)\s+(\S+)$/u', $label, $matches) !== 1) {
            return $label;
        }

        $suffix = mb_strtolower($matches[2]);
        if (!isset(self::STREET_SUFFIXES[$suffix])) {
            return $label;
        }

        $name = trim($matches[1]);
        if ($name === '') {
            return $label;
        }

        return self::STREET_SUFFIXES[$suffix] . ' ' . $name;
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
