<?php

namespace Khalil1608\LibBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class DeleteButtonType extends AbstractType
{

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getFormTheme(): array
    {
        return ['@Khalil1608Lib/Form/Type/delete_button.html.twig'];
    }

    public function getBlockPrefix(): string
    {
        // Prefix used for Twig form block name
        return 'delete_button';
    }
    
}