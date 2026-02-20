<?php

namespace UbeeDev\LibBundle\Config;

use BackedEnum;
use UbeeDev\LibBundle\Config\CustomEnumInterface;
use UbeeDev\LibBundle\Entity\Date;
use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Model\Type\Email;
use UbeeDev\LibBundle\Model\Type\Name;
use UbeeDev\LibBundle\Model\Type\PhoneNumber;
use UbeeDev\LibBundle\Model\Type\Url;
use Money\Money;
use Symfony\Component\HttpFoundation\File\UploadedFile;

enum ParameterType: string
{
    case STRING = 'string';
    case INT = 'int';
    case FLOAT = 'float';
    case BOOL = 'bool';
    case ARRAY = 'array';
    case DATE = 'date';
    case DATETIME = 'datetime';
    case MONEY = 'money';
    case ENUM = 'enum';
    case CUSTOM_ENUM = 'customEnum';
    case EMAIL = 'email';
    case NAME = 'name';
    case URL = 'url';
    case PHONE_NUMBER = 'phoneNumber';
    case ENTITY = 'entity';
    case FILE = 'file';

    /**
     * Get all available types as an array
     */
    public static function values(): array
    {
        return array_map(fn(self $case) => $case->value, self::cases());
    }

    /**
     * Check if a type requires a class parameter
     */
    public function requiresClass(): bool
    {
        return match ($this) {
            self::ENUM, self::CUSTOM_ENUM, self::ENTITY => true,
            default => false,
        };
    }

    /**
     * Get the expected PHP type after sanitization
     */
    public function getExpectedPhpType(): string
    {
        return match ($this) {
            self::STRING => 'string',
            self::INT => 'int',
            self::FLOAT => 'float',
            self::BOOL => 'bool',
            self::ARRAY => 'array',
            self::DATE => Date::class,
            self::DATETIME => DateTime::class,
            self::MONEY => Money::class,
            self::EMAIL => Email::class,
            self::NAME => Name::class,
            self::URL => Url::class,
            self::PHONE_NUMBER => PhoneNumber::class,
            self::ENUM => BackedEnum::class,
            self::CUSTOM_ENUM => CustomEnumInterface::class,
            self::ENTITY => 'object',
            self::FILE => UploadedFile::class,
        };
    }

    /**
     * Check if this type supports HTML stripping
     */
    public function supportsHtmlStripping(): bool
    {
        return match ($this) {
            self::STRING, self::EMAIL, self::NAME, self::URL => true,
            default => false,
        };
    }
}
