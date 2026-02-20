<?php

namespace UbeeDev\LibBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks()]
class AbstractActivableEntity extends AbstractEntity
{
    #[ORM\Column(type: 'boolean', options: ["default" => false])]
    private bool $active = false;
    
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