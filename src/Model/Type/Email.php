<?php

namespace Khalil1608\LibBundle\Model\Type;

class Email implements \JsonSerializable
{
    public ?string $value = null;

    private function __construct(string $email)
    {
        $this->value = $email;
    }

    public static function tryFrom(?string $email): ?static
    {
        return self::getEmailFromString($email);
    }

    public static function from(string $email): static
    {
        $emailObject = self::getEmailFromString($email);

        if (!$emailObject) {
            throw new \ValueError('Invalid email '.$email);
        }

        return $emailObject;
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }

    private static function getEmailFromString(?string $string): ?Email
    {
        if (null === $string || '' === $string) {
            return null;
        }

        if (filter_var($string, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return new Email($string);
    }

    public function __toString(): string
    {
        return $this->value ?: '';
    }
}