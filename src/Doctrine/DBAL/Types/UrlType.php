<?php

namespace Khalil1608\LibBundle\Doctrine\DBAL\Types;

use Khalil1608\LibBundle\Model\Type\Url;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\StringType;

/**
 * Type that maps an SQL string to an Url.
 */
class UrlType extends StringType
{
    public const URL = 'url';
    private const DEFAULT_LENGTH = 255;

    public function getUrl(): string
    {
        return self::URL;
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

        if ($value instanceof Url) {
            return $value->value;
        }

        throw ConversionException::conversionFailedInvalidType($value, Type::Url, ['null', 'Url']);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): ?Url
    {
        return $value === null ? null : Url::from($value);
    }
}
