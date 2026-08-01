<?php

declare(strict_types=1);

namespace App\Application\Query\Message\GetConversations;

use App\Application\DTO\ConversationDTO;
use App\Domain\BookingInquiry\Repository\BookingInquiryRepositoryInterface;
use App\Domain\Message\Repository\ConversationRepositoryInterface;
use App\Domain\Property\Repository\CityRepositoryInterface;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Property\Repository\StreetRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use App\Domain\User\Repository\UserRepositoryInterface;

final class GetConversationsHandler
{
    public function __construct(
        private readonly ConversationRepositoryInterface $conversationRepository,
        private readonly PropertyRepositoryInterface $propertyRepository,
        private readonly CityRepositoryInterface $cityRepository,
        private readonly StreetRepositoryInterface $streetRepository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly BookingInquiryRepositoryInterface $bookingInquiryRepository,
    ) {
    }

    public function __invoke(GetConversationsQuery $query): array
    {
        $conversations = $this->conversationRepository->findByUser(
            $query->userId,
            $query->page,
            $query->limit,
        );
        $total = $this->conversationRepository->countByUser($query->userId);

        $dtos = [];
        foreach ($conversations as $conversation) {
            $property = $this->propertyRepository->findById(
                Id::fromString($conversation->getPropertyId())
            );
            $seller = $this->userRepository->findById(
                Id::fromString($conversation->getSellerId())
            );
            $buyer = $this->userRepository->findById(
                Id::fromString($conversation->getBuyerId())
            );

            $propertyAvailable = false;
            $propertyLinkAvailable = false;
            $propertyImage = null;
            $propertyTitle = null;
            $propertyType = null;
            $propertyCitySlug = null;
            $propertyRegionName = null;
            $propertyPriceAmount = null;
            $propertyPriceCurrency = null;
            $propertyAddress = null;

            if ($property !== null) {
                $isOwner = $property->isOwnedBy($query->userId);
                $status = $property->getStatus();
                $propertyAvailable = match (true) {
                    $status === 'deleted' => false,
                    $status === 'published' => true,
                    $isOwner => true,
                    default => false,
                };
                $propertyLinkAvailable = $status === 'published';
                $propertyTitle = $property->getTitle();
                $propertyType = $property->getType();

                $images = $property->getImages();
                if (count($images) > 0) {
                    $propertyImage = $images[0];
                }

                if ($propertyAvailable) {
                    $city = $this->cityRepository->findById($property->getCityId());
                    $street = $property->getStreetId() !== null
                        ? $this->streetRepository->findById($property->getStreetId())
                        : null;
                    $streetName = $street !== null
                        ? ($street->getType() !== null && $street->getType() !== ''
                            ? $street->getType() . ' ' . $street->getName()
                            : $street->getName())
                        : $property->getStreetName();

                    $addressParts = array_filter([
                        $streetName,
                        $property->getAddress()->getBuilding(),
                        $city?->getName(),
                    ]);

                    $region = $city?->getRegionDistrict()?->getRegion();

                    $propertyCitySlug = $city?->getSlug();
                    $propertyRegionName = $region?->getName();
                    $propertyPriceAmount = $property->getPrice()->getAmount();
                    $propertyPriceCurrency = $property->getPrice()->getCurrency();
                    $propertyAddress = $addressParts !== [] ? implode(', ', $addressParts) : null;
                }
            }

            $bookingInquiry = null;
            $bookingInquiryId = $conversation->getBookingInquiryId();
            if ($bookingInquiryId !== null) {
                $bookingInquiry = $this->bookingInquiryRepository->findById($bookingInquiryId);
            }

            $dtos[] = ConversationDTO::fromEntity(
                $conversation,
                $query->userId,
                propertyTitle: $propertyTitle,
                propertyImage: $propertyImage,
                propertyType: $propertyType,
                propertyCitySlug: $propertyCitySlug,
                propertyRegionName: $propertyRegionName,
                propertyPriceAmount: $propertyPriceAmount,
                propertyPriceCurrency: $propertyPriceCurrency,
                propertyAddress: $propertyAddress,
                propertyAvailable: $propertyAvailable,
                propertyLinkAvailable: $propertyLinkAvailable,
                sellerName: $seller?->getFullName(),
                buyerName: $buyer?->getFullName(),
                bookingInquiry: $bookingInquiry,
            );
        }

        return [
            'items' => array_map(
                static fn (ConversationDTO $dto): array => $dto->jsonSerialize(),
                $dtos,
            ),
            'total' => $total,
            'page' => $query->page,
            'limit' => $query->limit,
        ];
    }
}
