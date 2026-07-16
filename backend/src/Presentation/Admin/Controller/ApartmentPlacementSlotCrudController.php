<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class ApartmentPlacementSlotCrudController extends AbstractPlacementSlotCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::Apartment->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'Диапазон позиций (квартиры)';
    }

    protected function entityLabelPlural(): string
    {
        return 'Диапазоны позиций — квартиры';
    }
}
