<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Service;

use App\Infrastructure\Service\GeocoderPlaceExtractor;
use PHPUnit\Framework\TestCase;

final class GeocoderPlaceExtractorTest extends TestCase
{
    private GeocoderPlaceExtractor $extractor;

    protected function setUp(): void
    {
        $this->extractor = new GeocoderPlaceExtractor();
    }

    public function testExtractsAdminDistrictMicrodistrictAndComplex(): void
    {
        $places = $this->extractor->extract($this->sampleResponse());

        self::assertSame('Советский район', $places->adminDistrictOfficialName);
        self::assertContains('микрорайон Комаровка', $places->microdistrictOfficialNames);
        self::assertSame('экспериментальный многофункциональный комплекс Минск-Мир', $places->primaryResidentialComplexOfficialName());
    }

    public function testSkipsNumberedMicrodistrictParts(): void
    {
        self::assertTrue($this->extractor->isNumberedPart('микрорайон Уручье-3'));
        self::assertFalse($this->extractor->isNumberedPart('микрорайон Уручье'));
    }

    public function testDoesNotTreatMinskMirQuartersAsSeparateComplexes(): void
    {
        self::assertFalse($this->extractor->isResidentialComplexName('квартал Тропические Острова'));
        self::assertTrue($this->extractor->isResidentialComplexName('экспериментальный многофункциональный комплекс Минск-Мир'));
    }

    /**
     * @return array<string, mixed>
     */
    private function sampleResponse(): array
    {
        return [
            'response' => [
                'GeoObjectCollection' => [
                    'featureMember' => [
                        [
                            'GeoObject' => [
                                'name' => 'микрорайон Комаровка',
                                'metaDataProperty' => [
                                    'GeocoderMetaData' => [
                                        'kind' => 'district',
                                        'Address' => [
                                            'Components' => [
                                                ['kind' => 'district', 'name' => 'микрорайон Комаровка'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'GeoObject' => [
                                'name' => 'Советский район',
                                'metaDataProperty' => [
                                    'GeocoderMetaData' => [
                                        'kind' => 'district',
                                        'Address' => [
                                            'Components' => [
                                                ['kind' => 'district', 'name' => 'Советский район'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        [
                            'GeoObject' => [
                                'name' => 'экспериментальный многофункциональный комплекс Минск-Мир',
                                'metaDataProperty' => [
                                    'GeocoderMetaData' => [
                                        'kind' => 'district',
                                        'Address' => [
                                            'Components' => [
                                                ['kind' => 'district', 'name' => 'экспериментальный многофункциональный комплекс Минск-Мир'],
                                                ['kind' => 'district', 'name' => 'квартал Тропические Острова'],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
