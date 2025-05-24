<?php

namespace  Khalil1608\LibBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;


#[\Attribute]
class ConstraintYoutubeUrl extends Constraint
{
    public $validationFailMessage = 'Cette valeur n\'est pas une url Youtube valide.';
    
    public function validatedBy(): string
    {
        return \get_class($this).'Validator';
    }
}
