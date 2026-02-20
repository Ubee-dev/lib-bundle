<?php

namespace UbeeDev\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
abstract class Address extends AbstractEntity implements \JsonSerializable
{
    #[Assert\Length(
        max: 10,
        maxMessage: 'Le numéro ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: 'string', nullable: true, length: 32)]
    private ?string $streetNumber = null;
    
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
    )]
    #[ORM\Column(type: 'string')]
    private string $street;
    
    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $complement = null;
    
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: 'string', length: 50)]
    private string $city;
    
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 50,
        maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: 'string', length: 50, options: ['default' => "France"])]
    private string $country = 'France';
    
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: 'Le code pastale ne peut pas dépasser {{ limit }} caractères.'
    )]
    #[ORM\Column(type: 'string', length: 32)]
    private string $postalCode;

    public function getStreetNumber(): ?string
    {
        return $this->streetNumber;
    }

    public function setStreetNumber(?string $streetNumber): self
    {
        $this->streetNumber = $streetNumber;
        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getComplement(): ?string
    {
        return $this->complement;
    }

    /**
     * @param string|null $complement
     * @return Address
     */
    public function setComplement(?string $complement): Address
    {
        $this->complement = $complement;
        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): self
    {
        $this->city = $city;
        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): self
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getAddressLine1(): string
    {
        return $this->getStreetNumber() . ' ' . $this->getStreet();
    }

    public function __toString(): string
    {
        $string = $this->getStreetNumber() . ' ' . $this->getStreet() . ', ';

        $string.= $this->getComplement() ? $this->getComplement().', ' : '';
        return $string.$this->getPostalCode() . ' ' . $this->getCity() . ', ' .
            $this->getCountry();
    }

    #[ArrayShape([
        'streetNumber' => "null|string", 
        'street' => "string",
        'complement' => "null|string", 
        'city' => "string", 
        'country' => "string", 
        'postalCode' => "string"
    ])] 
    public function jsonSerialize(): array
    {
        if($this->getId()) {
            return [
                'streetNumber' => $this->getStreetNumber(),
                'street' => $this->getStreet(),
                'complement' => $this->getComplement(),
                'city' => $this->getCity(),
                'country' => $this->getCountry(),
                'postalCode' => $this->getPostalCode(),
            ];
        } else {
            return [
                'streetNumber' => null,
                'street' => null,
                'complement' => null,
                'city' => null,
                'country' => null,
                'postalCode' => null,
            ];
        }

    }
}
