<?php


namespace Khalil1608\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
abstract class FooterLink extends AbstractEntity
{
    #[Assert\NotBlank]
    #[Assert\Length(max: 30)]
    #[ORM\Column(type: 'string', length: 30)]
    private string $label;
    
    #[Assert\NotBlank]
    #[ORM\Column(type: 'string')]
    private string $url;
    
    #[Assert\NotNull]
    #[ORM\Column(type: 'integer')]
    private int $position = 0;

    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private bool $active = false;

    public function getLabel(): string
    {
        return $this->label;
    }
    
    public function setLabel(string $label): self
    {
        $this->label = $label;
        return $this;
    }
    
    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }
    
    public function getPosition(): int
    {
        return $this->position;
    }
    
    public function setPosition(int $position): self
    {
        $this->position = $position;
        return $this;
    }
    
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }
}

