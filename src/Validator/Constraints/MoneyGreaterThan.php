<?php
namespace UbeeDev\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\AbstractComparison;

#[\Attribute]
class MoneyGreaterThan extends AbstractComparison
{
    public string $message = 'ubee_dev_lib.money.greather_than';
}
