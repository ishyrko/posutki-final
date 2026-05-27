<?php

declare(strict_types=1);

namespace App\Application\Query\BookingInquiry\GetMyBookingInquiries;

use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\StreetRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;

final class GetMyBookingInquiriesHandler
{
    public function __construct(
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly StreetRepositoryInterface $streetRepository,
    ) {
    }

    public function __invoke(GetMyBookingInquiriesQuery $query): array
    {
        $inquiries = $this->bookingInquiryRepository->findByOwnerId(
            $query->ownerId,
            $query->page,
            $query->limit,
        );
        $total = $this->bookingInquiryRepository->countByOwnerId($query->ownerId);

        $items = [];
        foreach ($inquiries as $inquiry) {
            $property = $this->propertyRepository->findById($inquiry->getPropertyId());
            $images = $property?->getImages() ?? [];
            $propertyImage = count($images) > 0 ? $images[0] : null;

            $city = $property !== null ? $this->cityRepository->findById($property->getCityId()) : null;
            $street = $property?->getStreetId() !== null
                ? $this->streetRepository->findById($property->getStreetId())
                : null;
            $streetName = $street !== null
                ? ($street->getType() !== null && $street->getType() !== ''
                    ? $street->getType() . ' ' . $street->getName()
                    : $street->getName())
                : $property?->getStreetName();

            $addressParts = array_filter([
                $streetName,
                $property?->getAddress()->getBuilding(),
                $city?->getName(),
            ]);

            $items[] = [
                'id' => (string) $inquiry->getId()->getValue(),
                'propertyId' => (string) $inquiry->getPropertyId()->getValue(),
                'propertyTitle' => $property?->getTitle(),
                'propertyImage' => $propertyImage,
                'propertyAddress' => $addressParts !== [] ? implode(', ', $addressParts) : null,
                'propertyPriceAmount' => $property?->getPrice()->getAmount(),
                'propertyPriceCurrency' => $property?->getPrice()->getCurrency(),
                'userId' => $inquiry->getUserId() !== null
                    ? (string) $inquiry->getUserId()->getValue()
                    : null,
                'name' => $inquiry->getName(),
                'phone' => $inquiry->getPhone(),
                'email' => $inquiry->getEmail(),
                'guests' => $inquiry->getGuests(),
                'checkIn' => $inquiry->getCheckIn()?->format('Y-m-d'),
                'checkOut' => $inquiry->getCheckOut()?->format('Y-m-d'),
                'notes' => $inquiry->getNotes(),
                'createdAt' => $inquiry->getCreatedAt()->format(\DateTimeInterface::ATOM),
                'isRead' => $inquiry->isRead(),
            ];
        }

        return [
            'items' => $items,
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }
}
