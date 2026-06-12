<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Property\Entity\PropertyAvailabilityBlock;
use Sabre\VObject\Component\VCalendar;

final class IcsExportService
{
    /**
     * @param PropertyAvailabilityBlock[] $blocks
     */
    public function buildCalendar(array $blocks, string $calendarName): string
    {
        $vcalendar = new VCalendar([
            'VERSION' => '2.0',
            'PRODID' => '-//posutki.by//Calendar//RU',
            'CALSCALE' => 'GREGORIAN',
            'METHOD' => 'PUBLISH',
            'X-WR-CALNAME' => $calendarName,
        ]);

        foreach ($blocks as $block) {
            $start = $block->getStartDate();
            $end = $block->getEndDate();
            $exclusiveEnd = $end->modify('+1 day');

            $vevent = $vcalendar->createComponent('VEVENT');
            $vevent->UID = sprintf('posutki-block-%s@posutki.by', (string) $block->getId()->getValue());
            $vevent->DTSTAMP = gmdate('Ymd\THis\Z');
            $vevent->DTSTART = $start->format('Ymd');
            $vevent->DTSTART['VALUE'] = 'DATE';
            $vevent->DTEND = $exclusiveEnd->format('Ymd');
            $vevent->DTEND['VALUE'] = 'DATE';
            $vevent->SUMMARY = 'Занято';
            $vevent->TRANSP = 'OPAQUE';

            $note = $block->getNote();
            if ($note !== null) {
                $vevent->DESCRIPTION = $note;
            }

            $vcalendar->add($vevent);
        }

        return $vcalendar->serialize();
    }
}
