<?php

namespace  Khalil1608\LibBundle\Validator\Constraints;


use Symfony\Component\Validator\Constraint;

#[\Attribute]
class ConstraintFacebookEmbedUrl extends Constraint
{
    public $validationFailMessage = 'ceci n\'est pas une url Facebook valide';


    public function validatedBy(): string
    {
        return \get_class($this).'Validator';
    }
}
