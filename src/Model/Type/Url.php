<?php

namespace Khalil1608\LibBundle\Model\Type;

class Url implements \JsonSerializable
{
    public ?string $value = null;

    private function __construct(string $url)
    {
        $this->value = $url;
    }

    public static function tryFrom(?string $url): ?static
    {
        return self::geturlFromString($url);
    }

    public static function from(string $url): static
    {
        $urlObject = self::geturlFromString($url);

        if (!$urlObject) {
            throw new \ValueError('Invalid url '.$url);
        }

        return $urlObject;
    }

    public function addPath(string $path): self
    {
        // remove double slashes
        $currentUrl = preg_replace('/([^:])(\/{2,})/', '$1/', $this->value.$path);
        return $this::from($currentUrl);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value ?: '';
    }

    private static function getUrlFromString(?string $string): ?url
    {
        $url = trim($string);
        if (null === $url || '' === $url) {
            return null;
        }

        if (!self::isValidUrl($url)) {
            return null;
        }

        return new Url(trim($url));
    }
    
    private static function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL);
    }
}