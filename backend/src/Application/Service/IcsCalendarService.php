<?php

declare(strict_types=1);

namespace App\Application\Service;

use Sabre\VObject\Component\VCalendar;
use Sabre\VObject\Component\VEvent;
use Sabre\VObject\Reader;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IcsCalendarService
{
    private const int FETCH_TIMEOUT_SECONDS = 5;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    /**
     * @param list<string> $urls
     *
     * @return array{
     *     blockedRanges: list<array{start: string, end: string}>,
     *     lastUpdatedAt: ?string,
     *     successfulFetches: int
     * }
     */
    public function fetchCalendarData(array $urls): array
    {
        $blockedRanges = [];
        $lastUpdatedAt = null;
        $successfulFetches = 0;

        foreach ($urls as $url) {
            if (!is_string($url)) {
                continue;
            }

            $url = trim($url);
            if ($url === '') {
                continue;
            }

            try {
                $calendarData = $this->fetchSingleCalendar($url);
                ++$successfulFetches;
                $blockedRanges = [...$blockedRanges, ...$calendarData['blockedRanges']];

                if ($calendarData['lastUpdatedAt'] !== null) {
                    if ($lastUpdatedAt === null || $calendarData['lastUpdatedAt'] > $lastUpdatedAt) {
                        $lastUpdatedAt = $calendarData['lastUpdatedAt'];
                    }
                }
            } catch (\Throwable) {
                continue;
            }
        }

        return [
            'blockedRanges' => $this->mergeBlockedRanges($blockedRanges),
            'lastUpdatedAt' => $lastUpdatedAt,
            'successfulFetches' => $successfulFetches,
        ];
    }

    /**
     * @return array{
     *     blockedRanges: list<array{start: string, end: string}>,
     *     lastUpdatedAt: ?string
     * }
     */
    private function fetchSingleCalendar(string $url): array
    {
        $response = $this->httpClient->request('GET', $url, [
            'timeout' => self::FETCH_TIMEOUT_SECONDS,
            'max_redirects' => 3,
            'headers' => [
                'User-Agent' => 'posutki.by/1.0',
                'Accept' => 'text/calendar, application/calendar+json, */*',
            ],
        ]);

        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \RuntimeException(sprintf('Calendar fetch failed with HTTP %d', $statusCode));
        }

        $content = $response->getContent(false);
        if ($content === '') {
            return ['blockedRanges' => [], 'lastUpdatedAt' => null];
        }

        $document = Reader::read($content);
        if (!$document instanceof VCalendar) {
            return ['blockedRanges' => [], 'lastUpdatedAt' => null];
        }

        $blockedRanges = [];
        $lastUpdatedAt = $this->extractTimestamp($this->getPropertyValue($document, 'LAST-MODIFIED', 'DTSTAMP'));

        foreach ($document->select('VEVENT') as $event) {
            if (!$event instanceof VEvent) {
                continue;
            }

            $range = $this->extractEventRange($event);
            if ($range !== null) {
                $blockedRanges[] = $range;
            }

            $eventUpdatedAt = $this->extractTimestamp($this->getPropertyValue($event, 'LAST-MODIFIED', 'DTSTAMP'));
            if ($eventUpdatedAt !== null && ($lastUpdatedAt === null || $eventUpdatedAt > $lastUpdatedAt)) {
                $lastUpdatedAt = $eventUpdatedAt;
            }
        }

        return [
            'blockedRanges' => $blockedRanges,
            'lastUpdatedAt' => $lastUpdatedAt,
        ];
    }

    /**
     * @return array{start: string, end: string}|null
     */
    private function extractEventRange(VEvent $event): ?array
    {
        $dtStart = $event->DTSTART ?? null;
        if ($dtStart === null) {
            return null;
        }

        $startDate = $this->extractCalendarDate($dtStart);
        if ($startDate === null) {
            return null;
        }

        $exclusiveEndDate = null;
        if (isset($event->DTEND)) {
            $exclusiveEndDate = $this->extractCalendarDate($event->DTEND);
        } elseif (isset($event->DURATION)) {
            $startDateTime = $dtStart->getDateTime();
            $exclusiveEndDate = $this->extractCalendarDateFromDateTime(
                $startDateTime->add($event->DURATION->getDateInterval()),
            );
        } elseif ($this->isAllDayDateProperty($dtStart)) {
            $exclusiveEndDate = (new \DateTimeImmutable($startDate))->modify('+1 day')->format('Y-m-d');
        } else {
            // RFC 5545: DATE-TIME без DTEND заканчивается в момент DTSTART.
            return ['start' => $startDate, 'end' => $startDate];
        }

        if ($exclusiveEndDate === null) {
            return ['start' => $startDate, 'end' => $startDate];
        }

        $inclusiveEndDate = (new \DateTimeImmutable($exclusiveEndDate))->modify('-1 day')->format('Y-m-d');
        if ($inclusiveEndDate < $startDate) {
            return ['start' => $startDate, 'end' => $startDate];
        }

        return ['start' => $startDate, 'end' => $inclusiveEndDate];
    }

    private function isAllDayDateProperty(mixed $property): bool
    {
        if (isset($property['VALUE']) && (string) $property['VALUE'] === 'DATE') {
            return true;
        }

        return preg_match('/^\d{8}$/', (string) $property) === 1;
    }

    private function extractCalendarDate(mixed $property): ?string
    {
        $raw = (string) $property;
        if (preg_match('/^\d{8}$/', $raw) === 1) {
            return sprintf('%s-%s-%s', substr($raw, 0, 4), substr($raw, 4, 2), substr($raw, 6, 2));
        }

        try {
            return $this->extractCalendarDateFromDateTime($property->getDateTime());
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractCalendarDateFromDateTime(\DateTimeInterface $dateTime): string
    {
        if ($dateTime instanceof \DateTimeImmutable) {
            return $dateTime->format('Y-m-d');
        }

        return \DateTimeImmutable::createFromInterface($dateTime)->format('Y-m-d');
    }

    private function extractTimestamp(mixed $property): ?string
    {
        if ($property === null) {
            return null;
        }

        try {
            return $property->getDateTime()->format('c');
        } catch (\Throwable) {
            return null;
        }
    }

    private function getPropertyValue(object $component, string $primary, string $fallback): mixed
    {
        if (isset($component->{$primary})) {
            return $component->{$primary};
        }

        if (isset($component->{$fallback})) {
            return $component->{$fallback};
        }

        return null;
    }

    /**
     * @param list<array{start: string, end: string}> $ranges
     *
     * @return list<array{start: string, end: string}>
     */
    private function mergeBlockedRanges(array $ranges): array
    {
        if ($ranges === []) {
            return [];
        }

        usort($ranges, static fn(array $a, array $b): int => strcmp($a['start'], $b['start']));

        $merged = [];
        foreach ($ranges as $range) {
            if ($merged === []) {
                $merged[] = $range;
                continue;
            }

            $lastIndex = count($merged) - 1;
            $last = $merged[$lastIndex];
            $nextStart = (new \DateTimeImmutable($range['start']))->modify('-1 day')->format('Y-m-d');

            if ($range['start'] <= $last['end'] || $nextStart <= $last['end']) {
                $merged[$lastIndex]['end'] = max($last['end'], $range['end']);
                continue;
            }

            $merged[] = $range;
        }

        return $merged;
    }
}
