<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Import;

use App\Domain\Property\Entity\City;
use App\Infrastructure\Import\PartnerListingTitleBuilder;
use PHPUnit\Framework\TestCase;

final class PartnerListingTitleBuilderTest extends TestCase
{
    private PartnerListingTitleBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PartnerListingTitleBuilder();
    }

    public function testBuildsRoomsGuestsAndPrepositionalCity(): void
    {
        $title = $this->builder->build(
            2,
            5,
            $this->city('Солигорск', 'Солигорске'),
            'Козлова ул.',
        );

        self::assertSame('2-комнатная квартира для 5 гостей в Солигорске', $title);
    }

    public function testFallsBackToStreetWhenGuestsUnknown(): void
    {
        $title = $this->builder->build(
            2,
            null,
            $this->city('Гродно', 'Гродно'),
            'Янки Купалы',
        );

        self::assertSame('2-комнатная квартира в Гродно, Янки Купалы', $title);
    }

    public function testNormalizesCatalogStreetSuffix(): void
    {
        $city = $this->city('Сморгонь', 'Сморгони');

        self::assertSame(
            '2-комнатная квартира в Сморгони, ул. Козлова',
            $this->builder->build(2, null, $city, 'Козлова ул.'),
        );
        self::assertSame(
            '3-комнатная квартира в Сморгони, пер. Крупской',
            $this->builder->build(3, null, $city, 'Крупской пер.'),
        );
        self::assertSame(
            '1-комнатная квартира в Сморгони, пр-т Независимости',
            $this->builder->build(1, null, $city, 'Независимости пр-т'),
        );
        self::assertSame(
            '2-комнатная квартира в Сморгони, пр-д Гагарина',
            $this->builder->build(2, null, $city, 'Гагарина пр-д'),
        );
    }

    public function testForceStreetKeepsGuestsAndAddsStreet(): void
    {
        $title = $this->builder->build(
            2,
            5,
            $this->city('Солигорск', 'Солигорске'),
            'Козлова ул.',
            true,
        );

        self::assertSame('2-комнатная квартира для 5 гостей в Солигорске, ул. Козлова', $title);
    }

    public function testUsesCityFallbackWithoutPrepositional(): void
    {
        $city = $this->city('Мачулищи г.п.', null, 'Мачулищи г.п.');

        self::assertSame(
            '3-комнатная квартира для 4 гостей в г. Мачулищи г.п.',
            $this->builder->build(3, 4, $city, 'Молодежная'),
        );
    }

    public function testTreatsMissingRoomsAsGenericApartment(): void
    {
        $title = $this->builder->build(
            null,
            2,
            $this->city('Минск', 'Минске'),
            'Независимости',
        );

        self::assertSame('Квартира для 2 гостей в Минске', $title);
    }

    public function testSingularGuestWord(): void
    {
        $title = $this->builder->build(
            1,
            1,
            $this->city('Минск', 'Минске'),
            null,
        );

        self::assertSame('1-комнатная квартира для 1 гостя в Минске', $title);
    }

    public function testTruncatesToMaxLength(): void
    {
        $title = $this->builder->build(
            2,
            null,
            $this->city('Минск', 'Минске'),
            str_repeat('А', 250),
        );

        self::assertSame(PartnerListingTitleBuilder::MAX_LENGTH, mb_strlen($title));
    }

    public function testFourRoomsPhrase(): void
    {
        $title = $this->builder->build(
            4,
            8,
            $this->city('Гродно', 'Гродно'),
            null,
        );

        self::assertSame('4-комнатная квартира для 8 гостей в Гродно', $title);
    }

    private function city(string $name, ?string $prepositional, string $shortName = ''): City
    {
        $city = new City();
        $city->setName($name);
        $city->setShortName($shortName !== '' ? $shortName : $name);
        $city->setNamePrepositional($prepositional);

        return $city;
    }
}
