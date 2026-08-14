<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\CityDistrict;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class CityDistrictCrudController extends AbstractCatalogPlaceCrudController
{
    public static function getEntityFqcn(): string
    {
        return CityDistrict::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Район города')
            ->setEntityLabelInPlural('Районы города')
            ->setSearchFields(['name', 'officialName', 'slug']);
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->catalogPlaceFields();
    }
}
