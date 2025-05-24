<?php

namespace Khalil1608\LibBundle\Model\Type;

class Name implements \JsonSerializable
{
    public ?string $value = null;

    private function __construct(string $name)
    {
        $this->value = $name;
    }

    public static function tryFrom(?string $name): ?static
    {
        return self::getNameFromString($name);
    }

    public static function from(string $name): static
    {
        $nameObject = self::getNameFromString($name);

        if (!$nameObject) {
            throw new \ValueError('Invalid name '.$name);
        }

        return $nameObject;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value ?: '';
    }

    private static function getNameFromString(?string $string): ?name
    {
        $name = trim($string);
        if (!$name) {
            return null;
        }

        if (!self::isValidName($name)) {
            return null;
        }

        return new Name($name);
    }
    
    private static function isValidName(string $name): bool
    {
        
        $regexPattern = "/^[a-zA-Z\'\’\.\u{00C0}-\u{017F}\s-]+$/u";
        return (bool)preg_match($regexPattern, $name);
    }
}