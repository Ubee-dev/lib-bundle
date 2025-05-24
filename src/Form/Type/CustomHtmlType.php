<?php

namespace Khalil1608\LibBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CustomHtmlType extends AbstractType
{
    public function getParent(): string
    {
        return TextType::class;
    }
    
    public function getBlockPrefix(): string
    {
        // Prefix used for Twig form block name
        return 'custom_html';
    }
}
