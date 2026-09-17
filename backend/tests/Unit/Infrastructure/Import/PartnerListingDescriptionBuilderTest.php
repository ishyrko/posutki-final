<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Import;

use App\Domain\Property\Entity\City;
use App\Infrastructure\Import\PartnerListingDescriptionBuilder;
use App\Infrastructure\Import\PartnerListingTitleBuilder;
use PHPUnit\Framework\TestCase;

final class PartnerListingDescriptionBuilderTest extends TestCase
{
    private PartnerListingDescriptionBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new PartnerListingDescriptionBuilder(new PartnerListingTitleBuilder());
    }

    public function testBuildsFullDescriptionFromKnownFacts(): void
    {
        $description = $this->builder->build(
            rooms: 3,
            guests: 6,
            city: $this->city('Солигорск', 'Солигорске'),
            streetLabel: 'Козлова ул.',
            building: '5',
            externalId: '10865',
            hasBusinessDocs: true,
            minStayDays: 1,
            checkInTime: '14:00',
            checkOutTime: '12:00',
            metroStation: 'Уручье',
        );

        self::assertStringStartsWith(
            '3-комнатная квартира на сутки в Солигорске, ул. Козлова, д. 5.',
            $description,
        );
        self::assertTrue(
            str_contains($description, 'Подойдёт для компании до 6 гостей.')
            || str_contains($description, 'Размещение до 6 гостей.')
            || str_contains($description, 'Рассчитана на 6 гостей.'),
        );
        self::assertStringContainsString('Рядом станция метро Уручье.', $description);
        self::assertStringContainsString('Предоставляются отчётные документы для командированных.', $description);
        self::assertStringEndsWith('Бронирование от 1 суток, заезд с 14:00, выезд до 12:00.', $description);
        self::assertGreaterThan(50, mb_strlen($description));
    }

    public function testOmitsOptionalBlocks(): void
    {
        $description = $this->builder->build(
            rooms: 2,
            guests: null,
            city: $this->city('Гродно', 'Гродно'),
            streetLabel: null,
            building: '17',
            externalId: '1',
            hasBusinessDocs: false,
            minStayDays: 2,
            checkInTime: '15:00',
            checkOutTime: '11:00',
        );

        self::assertSame(
            '2-комнатная квартира на сутки в Гродно, д. 17. Бронирование от 2 суток, заезд с 15:00, выезд до 11:00.',
            $description,
        );
        self::assertGreaterThan(50, mb_strlen($description));
    }

    public function testGuestSentenceIsDeterministicForSameExternalId(): void
    {
        $first = $this->builder->build(
            rooms: 2,
            guests: 5,
            city: $this->city('Минск', 'Минске'),
            streetLabel: 'Независимости',
            building: '10',
            externalId: 'stable-id',
            hasBusinessDocs: false,
            minStayDays: 1,
            checkInTime: '14:00',
            checkOutTime: '12:00',
        );
        $second = $this->builder->build(
            rooms: 2,
            guests: 5,
            city: $this->city('Минск', 'Минске'),
            streetLabel: 'Независимости',
            building: '10',
            externalId: 'stable-id',
            hasBusinessDocs: false,
            minStayDays: 1,
            checkInTime: '14:00',
            checkOutTime: '12:00',
        );

        self::assertSame($first, $second);
    }

    public function testAppendsMetroBeforeBookingSentence(): void
    {
        $withoutMetro = $this->builder->build(
            rooms: 1,
            guests: 2,
            city: $this->city('Минск', 'Минске'),
            streetLabel: 'Острошицкая',
            building: '11',
            externalId: '330',
            hasBusinessDocs: false,
            minStayDays: 1,
            checkInTime: '14:00',
            checkOutTime: '12:00',
        );

        $withMetro = $this->builder->appendMetro($withoutMetro, 'Уручье');

        self::assertStringContainsString('Рядом станция метро Уручье.', $withMetro);
        self::assertStringEndsWith('Бронирование от 1 суток, заезд с 14:00, выезд до 12:00.', $withMetro);
        self::assertSame($withMetro, $this->builder->appendMetro($withMetro, 'Уручье'));
    }

    public function testFingerprintTreatsAddressOnlyDifferencesAsSameTemplate(): void
    {
        $a = 'Предлагаем Вашему вниманию просторные апартаменты расположенные в самом удачном месте города по адресу г.Сморгонь, Якуба Коласа, д.57 . Предоставление отчетных документов командированным.';
        $b = 'Предлагаем Вашему вниманию просторные апартаменты расположенные в самом удачном месте города по адресу г.Солигорск, Козлова, д.5 . Предоставление отчетных документов командированным.';
        $unique = 'Уютная квартира в центре города с отдельной спальней и кухней.';

        self::assertSame(
            PartnerListingDescriptionBuilder::fingerprint($a),
            PartnerListingDescriptionBuilder::fingerprint($b),
        );

        $counts = PartnerListingDescriptionBuilder::fingerprintCountsFromRows([
            ['description' => $a],
            ['description' => $b],
            ['description' => $a],
            ['description' => $unique],
        ]);

        self::assertTrue(PartnerListingDescriptionBuilder::isTemplate($a, $counts));
        self::assertFalse(PartnerListingDescriptionBuilder::isTemplate($unique, $counts));
        self::assertTrue(PartnerListingDescriptionBuilder::isTemplate('', $counts));
    }

    public function testDetectsBusinessDocsKeywords(): void
    {
        self::assertTrue(PartnerListingDescriptionBuilder::mentionsBusinessDocs(
            'Предоставление отчетных документов командированным.',
        ));
        self::assertTrue(PartnerListingDescriptionBuilder::mentionsBusinessDocs(
            'Есть отчётные документы.',
        ));
        self::assertFalse(PartnerListingDescriptionBuilder::mentionsBusinessDocs(
            'Уютная квартира у парка.',
        ));
    }

    private function city(string $name, ?string $prepositional): City
    {
        $city = new City();
        $city->setName($name);
        $city->setShortName($name);
        $city->setNamePrepositional($prepositional);

        return $city;
    }
}
