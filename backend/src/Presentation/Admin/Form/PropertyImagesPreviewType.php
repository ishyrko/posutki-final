<?php

declare(strict_types=1);

namespace App\Presentation\Admin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PropertyImagesPreviewType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'label' => false,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'property_images_preview';
    }
}
