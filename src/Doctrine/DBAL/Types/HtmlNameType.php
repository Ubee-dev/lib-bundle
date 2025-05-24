<?php

namespace Khalil1608\LibBundle\Doctrine\DBAL\Types;

use Khalil1608\LibBundle\Model\Type\HtmlName;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

/**
 * Type that maps an SQL string to an Name.
 */
class HtmlNameType extends StringType
{
    public const string NAME = 'htmlName';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, AbstractPlatform $platform): mixed
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof HtmlName) {
            return $value->value;
        }

        throw ConversionException::conversionFailedInvalidType($value, Type::HtmlName, ['null', 'HtmlName']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?HtmlName
    {
        return $value === null ? null : HtmlName::from($value);
    }
}
