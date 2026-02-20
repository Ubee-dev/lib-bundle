<?php


namespace UbeeDev\LibBundle\Model;


use UbeeDev\LibBundle\Entity\Address;

interface AddressInterface
{
    /** @return Address|null */
    public function getAddress() :?Address;
    
    /** @return $this */
    public function setAddress(?Address $address);
}