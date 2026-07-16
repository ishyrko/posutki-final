<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Enum\PropertyType;

final class HousePlacementSlotCrudController extends AbstractPlacementSlotCrudController
{
    protected function scopedPropertyType(): string
    {
        return PropertyType::House->value;
    }

    protected function entityLabelSingular(): string
    {
        return 'Диапазон позиций (дома)';
    }

    protected function entityLabelPlural(): string
    {
        return 'Диапазоны позиций — дома';
    }
}
