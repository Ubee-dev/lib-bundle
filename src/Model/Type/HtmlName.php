<?php

namespace UbeeDev\LibBundle\Model\Type;

class HtmlName implements \JsonSerializable
{
    public ?string $value = null;

    private function __construct(string $htmlName)
    {
        $this->value = $htmlName;
    }

    public static function tryFrom(?string $htmlName): ?static
    {
        return self::gethtmlNameFromString($htmlName);
    }

    public static function from(string $htmlName): static
    {
        $htmlNameObject = self::gethtmlNameFromString($htmlName);

        if (!$htmlNameObject) {
            throw new \ValueError('Invalid html name '.$htmlName);
        }

        return $htmlNameObject;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value ?: '';
    }

    private static function getHtmlNameFromString(?string $string): ?htmlName
    {
        $htmlName = trim($string);
        if (!$htmlName) {
            return null;
        }

        if (!self::isValidHtmlName($htmlName)) {
            return null;
        }

        return new HtmlName(trim($htmlName));
    }

    private static function isValidHtmlName(string $htmlName): bool
    {
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_\-]*(\[[a-zA-Z0-9_\-]*\])*$/', $htmlName) === 1;
    }
}