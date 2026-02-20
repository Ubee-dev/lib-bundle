<?php

namespace  UbeeDev\LibBundle\Validator\Constraints;



use Symfony\Component\Validator\Constraint;

#[\Attribute]
class DateStartDuringGivenDay extends Constraint
{
    public string $message = 'UbeeDev_interview.date.start_during_given_day';
    public string $mode = 'stric'; // If the constraint has configuration options, define them as public properties
    public string $propertyPath;
    public bool $includeMidnight = false;

    public function validatedBy(): string
    {
        return static::class.'Validator';
    }

}
