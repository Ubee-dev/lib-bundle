<?php

namespace UbeeDev\LibBundle\Doctrine\DBAL\Types;

use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Money\Money;

/**
 * Type that maps an SQL INT to a PHP money.
 */
class MoneyType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return Type::MONEY;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getIntegerTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return $value;
        }
        
        if ($value instanceof Money) {
            return $value->getAmount();
        }
        
        throw ConversionException::conversionFailedInvalidType($value, Type::MONEY, ['null', 'Money']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): mixed
    {
        return $value === null ? null : Money::EUR($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType(): ParameterType
    {
        return ParameterType::INTEGER;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform) : bool
    {
        return true;
    }
}
