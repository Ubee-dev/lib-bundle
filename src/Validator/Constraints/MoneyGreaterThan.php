<?php
namespace Khalil1608\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\AbstractComparison;

#[\Attribute]
class MoneyGreaterThan extends AbstractComparison
{
    public string $message = 'Khalil1608_lib.money.greather_than';
}
