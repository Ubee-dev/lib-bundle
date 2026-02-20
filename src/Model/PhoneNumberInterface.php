<?php

namespace UbeeDev\LibBundle\Model;

interface PhoneNumberInterface
{
    /**
     * @return string|null
     */
    public function getPhoneNumber(): ?string;

    /**
     * @param string|null $phoneNumber
     * @return $this
     */
    public function setPhoneNumber(?string $phoneNumber);

    /**
     * @return int|null
     */
    public function getCountryCallingCode(): ?int;

    /**
     * @param int|null $countryCallingCode
     * @return $this
     */
    public function setCountryCallingCode(?int $countryCallingCode);
}