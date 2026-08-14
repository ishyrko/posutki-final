<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\CityMicrodistrict;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class CityMicrodistrictCrudController extends AbstractCatalogPlaceCrudController
{
    public static function getEntityFqcn(): string
    {
        return CityMicrodistrict::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Микрорайон')
            ->setEntityLabelInPlural('Микрорайоны')
            ->setSearchFields(['name', 'officialName', 'slug']);
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->catalogPlaceFields();
    }
}
