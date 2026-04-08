<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Controller;

use App\Domain\Article\Entity\ArticleCategory;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ArticleCategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ArticleCategory::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Категория статей')
            ->setEntityLabelInPlural('Категории статей')
            ->setDefaultSort(['sortOrder' => 'ASC'])
            ->setSearchFields(['name', 'slug']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->hideOnForm();

        yield TextField::new('name', 'Название');
        yield TextField::new('slug', 'Slug')
            ->setHelp('Латиница, цифры и дефисы (например: novosti)');

        yield IntegerField::new('sortOrder', 'Порядок сортировки')
            ->setHelp('Меньше = выше в списке');
    }
}
