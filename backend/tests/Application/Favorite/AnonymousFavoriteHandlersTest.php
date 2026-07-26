<?php

declare(strict_types=1);

namespace App\Tests\Application\Favorite;

use App\Application\Command\Favorite\SyncVisitorFavorites\SyncVisitorFavoritesCommand;
use App\Application\Command\Favorite\SyncVisitorFavorites\SyncVisitorFavoritesHandler;
use App\Application\Command\Favorite\TrackAnonymousFavorite\TrackAnonymousFavoriteCommand;
use App\Application\Command\Favorite\TrackAnonymousFavorite\TrackAnonymousFavoriteHandler;
use App\Domain\Favorite\Entity\Favorite;
use App\Domain\Favorite\Repository\FavoriteAddEventRepositoryInterface;
use App\Domain\Favorite\Repository\FavoriteRepositoryInterface;
use App\Domain\Property\Entity\Property;
use App\Domain\Property\Repository\PropertyRepositoryInterface;
use App\Domain\Shared\ValueObject\Id;
use PHPUnit\Framework\TestCase;

final class AnonymousFavoriteHandlersTest extends TestCase
{
    public function testCreatesVisitorFavoriteForPublishedProperty(): void
    {
        $propertyId = Id::fromInt(10);
        $property = $this->createMock(Property::class);
        $property->method('getStatus')->willReturn('published');

        $favoriteRepository = $this->createMock(FavoriteRepositoryInterface::class);
        $favoriteRepository->expects(self::once())
            ->method('findByVisitorAndProperty')
            ->with('visitor-1', $propertyId)
            ->willReturn(null);
        $favoriteRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn(Favorite $favorite): bool => $favorite->getVisitorId() === 'visitor-1'
                && $favorite->getUserId() === null
                && $favorite->getPropertyId()->getValue() === 10));

        $favoriteAddEventRepository = $this->createMock(FavoriteAddEventRepositoryInterface::class);
        $favoriteAddEventRepository->expects(self::once())
            ->method('record')
            ->with($propertyId);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->with($propertyId)->willReturn($property);

        $handler = new TrackAnonymousFavoriteHandler($favoriteRepository, $favoriteAddEventRepository, $propertyRepository);
        $handler(new TrackAnonymousFavoriteCommand('visitor-1', '10'));
    }

    public function testSkipsWhenVisitorFavoriteAlreadyExists(): void
    {
        $propertyId = Id::fromInt(10);
        $property = $this->createMock(Property::class);
        $property->method('getStatus')->willReturn('published');

        $favoriteRepository = $this->createMock(FavoriteRepositoryInterface::class);
        $favoriteRepository->method('findByVisitorAndProperty')->willReturn($this->createMock(Favorite::class));
        $favoriteRepository->expects(self::never())->method('save');

        $favoriteAddEventRepository = $this->createMock(FavoriteAddEventRepositoryInterface::class);
        $favoriteAddEventRepository->expects(self::never())->method('record');

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $handler = new TrackAnonymousFavoriteHandler($favoriteRepository, $favoriteAddEventRepository, $propertyRepository);
        $handler(new TrackAnonymousFavoriteCommand('visitor-1', '10'));
    }

    public function testMergesVisitorFavoritesIntoUserAccount(): void
    {
        $userId = Id::fromInt(5);
        $propertyId = Id::fromInt(10);
        $property = $this->createMock(Property::class);

        $favoriteRepository = $this->createMock(FavoriteRepositoryInterface::class);
        $favoriteRepository->expects(self::once())
            ->method('findByUserAndProperty')
            ->with($userId, $propertyId)
            ->willReturn(null);
        $favoriteRepository->expects(self::once())
            ->method('save')
            ->with(self::callback(static fn(Favorite $favorite): bool => $favorite->getUserId()?->getValue() === 5
                && $favorite->getVisitorId() === null));
        $favoriteRepository->expects(self::once())
            ->method('deleteByVisitorAndProperty')
            ->with('visitor-1', $propertyId);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->with($propertyId)->willReturn($property);

        $handler = new SyncVisitorFavoritesHandler($favoriteRepository, $propertyRepository);
        $handler(new SyncVisitorFavoritesCommand('5', 'visitor-1', [10]));
    }

    public function testDoesNotDuplicateExistingUserFavorite(): void
    {
        $userId = Id::fromInt(5);
        $propertyId = Id::fromInt(10);
        $property = $this->createMock(Property::class);

        $favoriteRepository = $this->createMock(FavoriteRepositoryInterface::class);
        $favoriteRepository->method('findByUserAndProperty')->willReturn($this->createMock(Favorite::class));
        $favoriteRepository->expects(self::never())->method('save');
        $favoriteRepository->expects(self::once())
            ->method('deleteByVisitorAndProperty')
            ->with('visitor-1', $propertyId);

        $propertyRepository = $this->createMock(PropertyRepositoryInterface::class);
        $propertyRepository->method('findById')->willReturn($property);

        $handler = new SyncVisitorFavoritesHandler($favoriteRepository, $propertyRepository);
        $handler(new SyncVisitorFavoritesCommand('5', 'visitor-1', [10]));
    }
}
