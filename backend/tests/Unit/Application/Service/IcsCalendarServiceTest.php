<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Service;

use App\Application\Service\IcsCalendarService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class IcsCalendarServiceTest extends TestCase
{
    public function testFetchCalendarDataParsesBlockedRangesAndLastUpdatedAt(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
PRODID:-//posutki.by//test//EN
BEGIN:VEVENT
UID:test-event-1
DTSTAMP:20260520T120000Z
LAST-MODIFIED:20260522T183000Z
DTSTART;VALUE=DATE:20260601
DTEND;VALUE=DATE:20260604
SUMMARY:Booked
END:VEVENT
END:VCALENDAR
ICS;

        $service = new IcsCalendarService(new MockHttpClient([
            new MockResponse($ics, ['http_code' => 200]),
        ]));

        $result = $service->fetchCalendarData(['https://example.com/calendar.ics']);

        self::assertSame([
            ['start' => '2026-06-01', 'end' => '2026-06-03'],
        ], $result['blockedRanges']);
        self::assertSame('2026-05-22T18:30:00+00:00', $result['lastUpdatedAt']);
    }

    public function testFetchCalendarDataSkipsFailedUrls(): void
    {
        $ics = <<<ICS
BEGIN:VCALENDAR
VERSION:2.0
BEGIN:VEVENT
UID:test-event-2
DTSTAMP:20260521T100000Z
DTSTART;VALUE=DATE:20260701
DTEND;VALUE=DATE:20260702
SUMMARY:Booked
END:VEVENT
END:VCALENDAR
ICS;

        $service = new IcsCalendarService(new MockHttpClient([
            new MockResponse('', ['http_code' => 500]),
            new MockResponse($ics, ['http_code' => 200]),
        ]));

        $result = $service->fetchCalendarData([
            'https://broken.example/calendar.ics',
            'https://example.com/calendar.ics',
        ]);

        self::assertSame([
            ['start' => '2026-07-01', 'end' => '2026-07-01'],
        ], $result['blockedRanges']);
    }
}
