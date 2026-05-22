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
     *     lastUpdatedAt: ?string
     * }
     */
    public function fetchCalendarData(array $urls): array
    {
        $blockedRanges = [];
        $lastUpdatedAt = null;

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

        $start = $dtStart->getDateTime();
        $isAllDay = !str_contains((string) $dtStart, 'T');

        $end = null;
        if (isset($event->DTEND)) {
            $end = $event->DTEND->getDateTime();
        } elseif (isset($event->DURATION)) {
            $end = (clone $start)->add($event->DURATION->getDateInterval());
        } else {
            $end = clone $start;
            if ($isAllDay) {
                $end = $end->modify('+1 day');
            }
        }

        if ($isAllDay) {
            $startDate = $start->format('Y-m-d');
            $endDate = $end->format('Y-m-d');

            if ($startDate === $endDate) {
                return ['start' => $startDate, 'end' => $startDate];
            }

            $inclusiveEnd = (new \DateTimeImmutable($endDate))->modify('-1 day')->format('Y-m-d');

            return ['start' => $startDate, 'end' => $inclusiveEnd];
        }

        return [
            'start' => $start->format('Y-m-d'),
            'end' => $end->format('Y-m-d'),
        ];
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
