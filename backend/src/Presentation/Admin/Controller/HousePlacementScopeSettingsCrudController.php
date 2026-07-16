<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class HousePlacementScopeSettingsCrudController extends AbstractPlacementScopeSettingsCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::House->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'Настройки VIP (дома)';
    }

    protected function entityLabelPlural(): string
    {
        return 'Настройки VIP — дома';
    }
}
