<?php


namespace UbeeDev\LibBundle\Traits;

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberUtil;

trait PhoneNumberTrait
{

    /**
     * @param string $phoneNumber
     * @param int $countryCallingCode
     * @return |null
     */
    public function getFormattedPhoneNumber($countryCallingCode, $phoneNumber)
    {
        if (!$phoneNumber) {
            return null;
        }
        $value = '+' . $countryCallingCode . $phoneNumber;
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($value);
        return str_replace('.',' ', $phoneUtil->format($phoneNumber,PhoneNumberFormat::INTERNATIONAL));

    }

    /**
     * @param string $phoneNumber
     * @param int $countryCallingCode
     * @return string|null
     */
    public function getNationalFormattedNumber($phoneNumber, $countryCallingCode = 33) {
        if (!$phoneNumber) {
            return null;
        }
        $value = '+' . $countryCallingCode . $phoneNumber;
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($value);
        return $phoneUtil->format($phoneNumber,PhoneNumberFormat::NATIONAL);
    }
    
    public function getCountryCodeFromFormattedNumber($formattedNumber) {
        if (!$formattedNumber) {
            return null;
        }
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($formattedNumber);
        return $phoneNumber->getCountryCode();
    }

    public function getLocalNumberFromFormattedNumber($formattedNumber) {
        if (!$formattedNumber) {
            return null;
        }
        $phoneUtil = PhoneNumberUtil::getInstance();
        $phoneNumber = $phoneUtil->parse($formattedNumber);
        return $phoneNumber->getNationalNumber();
    }
}