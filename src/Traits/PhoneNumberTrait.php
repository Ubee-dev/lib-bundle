<?php


namespace UbeeDev\LibBundle\Traits;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

trait PhoneNumberTrait
{

    public function getFormattedPhoneNumber(int|string|null $countryCallingCode, ?string $phoneNumber): ?string
    {
        if (!$phoneNumber) {
            return null;
        }
        $value = '+' . $countryCallingCode . $phoneNumber;
        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsed = $phoneUtil->parse($value);
        return str_replace('.', ' ', $phoneUtil->format($parsed, PhoneNumberFormat::INTERNATIONAL));
    }

    public function getNationalFormattedNumber(string $phoneNumber, int|string $countryCallingCode = 33): ?string
    {
        if (!$phoneNumber) {
            return null;
        }
        $value = '+' . $countryCallingCode . $phoneNumber;
        $phoneUtil = PhoneNumberUtil::getInstance();
        $parsed = $phoneUtil->parse($value);
        return $phoneUtil->format($parsed, PhoneNumberFormat::NATIONAL);
    }

    public function getCountryCodeFromFormattedNumber(string $formattedNumber): ?int
    {
        if (!$formattedNumber) {
            return null;
        }
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($formattedNumber);
        return $phoneNumber->getCountryCode();
    }

    public function getLocalNumberFromFormattedNumber(string $formattedNumber): ?string
    {
        if (!$formattedNumber) {
            return null;
        }
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($formattedNumber);
        return $phoneNumber->getNationalNumber();
    }
}