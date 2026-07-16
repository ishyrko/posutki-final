<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class ApartmentPlacementScopeSettingsCrudController extends AbstractPlacementScopeSettingsCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::Apartment->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'Настройки VIP (квартиры)';
    }

    protected function entityLabelPlural(): string
    {
        return 'Настройки VIP — квартиры';
    }
}
