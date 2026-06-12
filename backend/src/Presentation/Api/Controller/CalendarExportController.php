<?php

declare(strict_types=1);

namespace App\Presentation\Api\Controller;

use App\Application\Service\IcsExportService;
use App\Domain\Property\Repository\PropertyAvailabilityBlockRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/ical', name: 'ical_')]
final class CalendarExportController extends AbstractController
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly PropertyAvailabilityBlockRepositoryInterface $availabilityBlockRepository,
        private readonly IcsExportService $icsExportService,
    ) {
    }

    #[Route('/{token}.ics', name: 'export', methods: ['GET'], requirements: ['token' => '[a-f0-9]{48}'])]
    public function export(string $token): Response
    {
        $property = $this->propertyRepository->findByCalendarExportToken($token);
        if ($property === null || $property->getStatus() === 'deleted') {
            return new Response('Not found', Response::HTTP_NOT_FOUND);
        }

        $blocks = $this->availabilityBlockRepository->findByPropertyId($property->getId());
        $ics = $this->icsExportService->buildCalendar($blocks, $property->getTitle());

        return new Response(
            $ics,
            Response::HTTP_OK,
            [
                'Content-Type' => 'text/calendar; charset=utf-8',
                'Content-Disposition' => 'inline; filename="posutki-calendar.ics"',
                'Cache-Control' => 'public, max-age=900',
            ],
        );
    }
}
