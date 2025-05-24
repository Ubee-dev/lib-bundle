<?php

namespace Khalil1608\LibBundle\Model\Type;

use libphonenumber\PhoneNumberUtil;
use libphonenumber\NumberParseException;

class PhoneNumber implements \JsonSerializable
{
    public ?string $countryCallingCode = null;
    public ?string $phoneNumber = null;

    private function __construct(string $countryCallingCode, string $phoneNumber)
    {
        $this->countryCallingCode = $countryCallingCode;
        $this->phoneNumber = $phoneNumber;
    }

    public static function tryFrom(?string $phoneNumber): ?static
    {
        return self::getPhoneNumberFromString($phoneNumber);
    }

    public static function from(string $phoneNumber): static
    {
        $phoneObject = self::getPhoneNumberFromString($phoneNumber);

        if (!$phoneObject) {
            throw new \ValueError('Invalid phone number '.$phoneNumber);
        }

        return $phoneObject;
    }

    public function jsonSerialize(): array
    {
        return [
            'countryCallingCode' => $this->countryCallingCode,
            'phoneNumber' => $this->phoneNumber
        ];
    }

    public function __toString(): string
    {
        return '+' . $this->countryCallingCode . $this->phoneNumber;
    }

    private static function getPhoneNumberFromString(?string $phoneNumber): ?PhoneNumber
    {
        if (!$phoneNumber) {
            return null;
        }

        try {
            $phoneUtil = PhoneNumberUtil::getInstance();
            $parsedNumber = $phoneUtil->parse($phoneNumber, 'FR');

            if (!$parsedNumber || !$phoneUtil->isValidNumber($parsedNumber)) {
                return null;
            }

            $countryCode = (string) $parsedNumber->getCountryCode();
            $nationalNumber = (string) $parsedNumber->getNationalNumber();

            return new PhoneNumber($countryCode, $nationalNumber);
        } catch (NumberParseException) {
            return null;
        }
    }
}