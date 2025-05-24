<?php

namespace Khalil1608\LibBundle\Validator\Constraints;

use Money\Money;
use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;
use Symfony\Component\Validator\Constraints\GreaterThan;

/**
 * Phone number validator.
 */
class MoneyGreaterThanValidator extends AbstractComparisonValidator
{
    /**
     * {@inheritdoc}
     */
    protected function compareValues(mixed $value1, mixed $value2): bool
    {
        return $value1 > Money::EUR($value2);
    }

    /**
     * {@inheritdoc}
     */
    protected function getErrorCode(): ?string
    {
        return GreaterThan::TOO_LOW_ERROR;
    }
}
