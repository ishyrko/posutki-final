<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class HousePlacementLevelPriceCrudController extends AbstractPlacementLevelPriceCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::House->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'VIP-тариф (дома)';
    }

    protected function entityLabelPlural(): string
    {
        return 'VIP-тарифы — дома';
    }
}
