<?php


namespace Khalil1608\LibBundle\Model;


use Khalil1608\LibBundle\Entity\Address;

interface AddressInterface
{
    /** @return Address|null */
    public function getAddress() :?Address;
    
    /** @return $this */
    public function setAddress(?Address $address);
}