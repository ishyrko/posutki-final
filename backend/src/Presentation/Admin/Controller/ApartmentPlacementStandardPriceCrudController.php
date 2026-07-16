<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class ApartmentPlacementStandardPriceCrudController extends AbstractPlacementStandardPriceCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::Apartment->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'Стандартное размещение (квартиры)';
    }

    protected function entityLabelPlural(): string
    {
        return 'Стандартное размещение — квартиры';
    }
}
