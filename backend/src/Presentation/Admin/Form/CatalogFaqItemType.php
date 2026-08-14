<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CatalogFaqItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, [
                'label' => 'Вопрос',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'placeholder' => 'Например: Как добраться до микрорайона?',
                ],
            ])
            ->add('answer', TextareaType::class, [
                'label' => 'Ответ',
                'required' => false,
                'empty_data' => '',
                'attr' => [
                    'rows' => 4,
                    'placeholder' => 'Краткий ответ для блока FAQ под каталогом',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'label' => false,
        ]);
    }
}
