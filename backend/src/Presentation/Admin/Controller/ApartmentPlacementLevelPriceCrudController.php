<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class ApartmentPlacementLevelPriceCrudController extends AbstractPlacementLevelPriceCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::Apartment->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'VIP-тариф (квартиры)';
    }

    protected function entityLabelPlural(): string
    {
        return 'VIP-тарифы — квартиры';
    }
}
