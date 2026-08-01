<?php

declare(strict_types=1);

namespace App\Tests\Application\Message;

use App\Application\DTO\ConversationDTO;
use App\Domain\BookingInquiry\Entity\BookingInquiry;
use App\Domain\Message\Entity\Conversation;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class ConversationDTOTest extends TestCase
{
    public function testSerializesBookingInquiryContext(): void
    {
        $conversation = new Conversation(
            propertyId: Id::fromInt(100),
            sellerId: Id::fromInt(10),
            buyerId: Id::fromInt(5),
        );
        $this->setEntityId($conversation, 42);
        $conversation->linkBookingInquiry(Id::fromInt(15));

        $inquiry = new BookingInquiry(
            propertyId: Id::fromInt(100),
            ownerId: Id::fromInt(10),
            name: 'Иван',
            phone: '+375291112233',
            userId: Id::fromInt(5),
            checkIn: new \DateTimeImmutable('2026-08-05'),
            checkOut: new \DateTimeImmutable('2026-08-07'),
            guests: 2,
        );
        $this->setInquiryId($inquiry, 15);

        $dto = ConversationDTO::fromEntity(
            $conversation,
            currentUserId: '10',
            propertyTitle: 'Квартира в центре',
            propertyImage: 'https://example.com/photo.jpg',
            propertyType: 'apartment',
            propertyCitySlug: 'minsk',
            propertyRegionName: 'Минск',
            propertyPriceAmount: 120,
            propertyPriceCurrency: 'BYN',
            propertyAddress: 'ул. Ленина, 1, Минск',
            propertyAvailable: true,
            propertyLinkAvailable: true,
            sellerName: 'Владелец',
            buyerName: 'Гость',
            bookingInquiry: $inquiry,
        );

        $data = $dto->jsonSerialize();

        self::assertSame('apartment', $data['propertyType']);
        self::assertSame('minsk', $data['propertyCitySlug']);
        self::assertSame(120, $data['propertyPriceAmount']);
        self::assertSame('ул. Ленина, 1, Минск', $data['propertyAddress']);
        self::assertTrue($data['propertyAvailable']);
        self::assertTrue($data['propertyLinkAvailable']);
        self::assertIsArray($data['bookingInquiry']);
        self::assertSame('15', $data['bookingInquiry']['id']);
        self::assertSame('2026-08-05', $data['bookingInquiry']['checkIn']);
        self::assertSame(2, $data['bookingInquiry']['guests']);
    }

    public function testMarksUnavailableProperty(): void
    {
        $conversation = new Conversation(
            propertyId: Id::fromInt(100),
            sellerId: Id::fromInt(10),
            buyerId: Id::fromInt(5),
        );
        $this->setEntityId($conversation, 42);

        $dto = ConversationDTO::fromEntity(
            $conversation,
            currentUserId: '10',
            propertyTitle: 'Снятое объявление',
            propertyAvailable: false,
            propertyLinkAvailable: false,
            sellerName: 'Владелец',
            buyerName: 'Гость',
        );

        $data = $dto->jsonSerialize();

        self::assertFalse($data['propertyAvailable']);
        self::assertFalse($data['propertyLinkAvailable']);
        self::assertSame('Снятое объявление', $data['propertyTitle']);
        self::assertNull($data['propertyImage']);
    }

    private function setEntityId(Conversation $conversation, int $id): void
    {
        $idReflection = new \ReflectionProperty($conversation, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($conversation, Id::fromInt($id));
    }

    private function setInquiryId(BookingInquiry $inquiry, int $id): void
    {
        $idReflection = new \ReflectionProperty($inquiry, 'id');
        $idReflection->setAccessible(true);
        $idReflection->setValue($inquiry, Id::fromInt($id));
    }
}
