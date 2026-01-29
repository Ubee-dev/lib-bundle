<?php

namespace Khalil1608\LibBundle\Builder;

use Khalil1608\LibBundle\Config\CustomEnumInterface;
use Khalil1608\LibBundle\Config\ParameterType;
use BackedEnum;

/**
 * Builder class for creating parameter expectations with IDE autocompletion support
 */
class ExpectationBuilder
{
    private array $expectation = [];

    protected function __construct(ParameterType $type)
    {
        $this->expectation['type'] = $type;
    }

    // Factory methods for each type
    public static function string(): StringExpectation
    {
        return new StringExpectation(ParameterType::STRING);
    }

    public static function int(): NumericExpectation
    {
        return new NumericExpectation(ParameterType::INT);
    }

    public static function float(): NumericExpectation
    {
        return new NumericExpectation(ParameterType::FLOAT);
    }

    public static function bool(): BasicExpectation
    {
        return new BasicExpectation(ParameterType::BOOL);
    }

    public static function array(): ArrayExpectation
    {
        return new ArrayExpectation(ParameterType::ARRAY);
    }

    public static function date(): BasicExpectation
    {
        return new BasicExpectation(ParameterType::DATE);
    }

    public static function datetime(): BasicExpectation
    {
        return new BasicExpectation(ParameterType::DATETIME);
    }

    public static function money(): BasicExpectation
    {
        return new BasicExpectation(ParameterType::MONEY);
    }

    public static function email(): StringExpectation
    {
        return new StringExpectation(ParameterType::EMAIL);
    }

    public static function name(): StringExpectation
    {
        return new StringExpectation(ParameterType::NAME);
    }

    public static function url(): StringExpectation
    {
        return new StringExpectation(ParameterType::URL);
    }

    public static function phoneNumber(): StringExpectation
    {
        return new StringExpectation(ParameterType::PHONE_NUMBER);
    }

    /**
     * @template T of BackedEnum
     * @param class-string<T> $enumClass
     */
    public static function enum(string $enumClass): EnumExpectation
    {
        return new EnumExpectation(ParameterType::ENUM, $enumClass);
    }

    /**
     * @template T of CustomEnumInterface
     * @param class-string<T> $enumClass
     */
    public static function customEnum(string $enumClass): EnumExpectation
    {
        return new EnumExpectation(ParameterType::CUSTOM_ENUM, $enumClass);
    }

    /**
     * @template T of object
     * @param class-string<T> $entityClass
     */
    public static function entity(string $entityClass): EntityExpectation
    {
        return new EntityExpectation(ParameterType::ENTITY, $entityClass);
    }

    public static function file(): FileExpectation
    {
        return new FileExpectation(ParameterType::FILE);
    }

    public function required(bool $required = true): static
    {
        $this->expectation['required'] = $required;
        return $this;
    }

    public function optional(): static
    {
        return $this->required(false);
    }

    public function toArray(): array
    {
        return $this->expectation;
    }

    protected function setExpectation(string $key, mixed $value): static
    {
        $this->expectation[$key] = $value;
        return $this;
    }
}
