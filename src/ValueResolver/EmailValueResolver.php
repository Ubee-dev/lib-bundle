<?php

namespace UbeeDev\LibBundle\ValueResolver;

use UbeeDev\LibBundle\Model\Type\Email;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

class EmailValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // get the argument type (e.g. Email)
        $argumentType = $argument->getType();

        if (
            !$argumentType
            || $argumentType !== Email::class
        ) {
            return [];
        }

        // get the value from the request, based on the argument name
        $value = $request->attributes->get($argument->getName());
        if (!is_string($value)) {
            return [];
        }

        // create and return the value object
        return [$argumentType::from($value)];
    }
}