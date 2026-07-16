<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class HousePlacementStandardPriceCrudController extends AbstractPlacementStandardPriceCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::House->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'Стандартное размещение (дома)';
    }

    protected function entityLabelPlural(): string
    {
        return 'Стандартное размещение — дома';
    }
}
