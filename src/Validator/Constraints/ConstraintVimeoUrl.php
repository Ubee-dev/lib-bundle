<?php

namespace  Khalil1608\LibBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;


#[\Attribute]
class ConstraintVimeoUrl extends Constraint
{
    public $validationFailMessage = 'ceci n\'est pas une url Vimeo valide';


    public function validatedBy(): string
    {
        return \get_class($this).'Validator';
    }
}
