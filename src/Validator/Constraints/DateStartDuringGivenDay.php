<?php

namespace  Khalil1608\LibBundle\Validator\Constraints;



use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DateStartDuringGivenDay extends Constraint
{
    public string $message = 'Khalil1608_interview.date.start_during_given_day';
    public string $mode = 'stric'; // If the constraint has configuration options, define them as public properties
    public string $propertyPath;
    public bool $includeMidnight = false;

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }

}
