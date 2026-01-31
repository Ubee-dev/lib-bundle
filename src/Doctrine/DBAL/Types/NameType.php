<?php

namespace Khalil1608\LibBundle\Doctrine\DBAL\Types;

use Khalil1608\LibBundle\Model\Type\Name;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

/**
 * Type that maps an SQL string to an Name.
 */
class NameType extends StringType
{
    public const NAME = 'name';
    private const DEFAULT_LENGTH = 255;

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        $column['length'] ??= self::DEFAULT_LENGTH;
        return parent::getSQLDeclaration($column, $platform);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof Name) {
            return $value->value;
        }

        throw ConversionException::conversionFailedInvalidType($value, Type::Name, ['null', 'Name']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Name
    {
        return $value === null ? null : Name::from($value);
    }
}
