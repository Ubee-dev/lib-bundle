<?php

namespace Khalil1608\LibBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LibButtonType extends AbstractType
{
    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getFormTheme(): array
    {
        return ['@Khalil1608Lib/Form/Type/lib_button.html.twig'];
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->setDefaults([
            'mapped' => false,
            'required' => false,
            'icon' => null,
            'requires_confirmation' => false,
            'execute_once' => false,
            'refresh' => false
        ]);
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        parent::buildView($view, $form, $options);
        $view->vars['icon'] = $options['icon'];
        $view->vars['requires_confirmation'] = $options['requires_confirmation'];
        $view->vars['execute_once'] = $options['execute_once'];
        $view->vars['refresh'] = $options['refresh'];
    }
}