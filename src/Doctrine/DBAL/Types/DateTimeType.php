<?php

namespace UbeeDev\LibBundle\Doctrine\DBAL\Types;

use UbeeDev\LibBundle\Entity\DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\Type;
use Doctrine\DBAL\Types\Types;

/**
 * Type that maps an SQL DATETIME/TIMESTAMP to a PHP DateTime object.
 */
class DateTimeType extends Type
{
    public const MYTYPE = Types::DATETIME_MUTABLE;

    public function getName(): string
    {
        return self::MYTYPE;
    }

    /**
     * {@inheritdoc}
     * @throws Exception
     */
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return $platform->getDateTimeTypeDeclarationSQL($column);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format($platform->getDateTimeFormatString());
        }

        throw ConversionException::conversionFailedInvalidType($value, $this->getName(), ['null', 'DateTime']);
    }

    /**
     * @param $value
     * @param AbstractPlatform $platform
     * @return DateTime|DateTimeInterface|null
     * @throws \Exception
     */
    public function convertToPHPValue($value, AbstractPlatform $platform): DateTimeInterface|DateTime|null
    {
        if ($value === null || $value instanceof DateTimeInterface) {
            return $value;
        }
        return new DateTime($value);
    }
}