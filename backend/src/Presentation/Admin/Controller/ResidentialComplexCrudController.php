<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Property\Entity\ResidentialComplex;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;

class ResidentialComplexCrudController extends AbstractCatalogPlaceCrudController
{
    public static function getEntityFqcn(): string
    {
        return ResidentialComplex::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Жилой комплекс')
            ->setEntityLabelInPlural('Жилые комплексы')
            ->setSearchFields(['name', 'officialName', 'slug']);
    }

    public function configureFields(string $pageName): iterable
    {
        return $this->catalogPlaceFields();
    }
}
