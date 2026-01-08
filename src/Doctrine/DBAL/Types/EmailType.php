<?php

namespace Khalil1608\LibBundle\Doctrine\DBAL\Types;

use Khalil1608\LibBundle\Model\Type\Email;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;

/**
 * Type that maps an SQL string to an Email.
 */
class EmailType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return Type::Email;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getStringTypeDeclarationSQL($column);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }
        
        if ($value instanceof Email) {
            return $value->value;
        }
        
        throw ConversionException::conversionFailedInvalidType($value, Type::Email, ['null', 'Email']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Email
    {
        return $value === null ? null : Email::from($value);
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType(): ParameterType
    {
        return ParameterType::STRING;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform) : bool
    {
        return true;
    }
}
