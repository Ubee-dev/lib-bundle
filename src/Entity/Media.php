<?php


namespace Khalil1608\LibBundle\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks()]
abstract class Media extends AbstractEntity
{
    #[Assert\NotNull]
    #[ORM\Column(type: 'string')]
    private string $filename;
    
    #[Assert\NotNull]
    #[ORM\Column(type: 'string')]
    private string $context;
    
    #[Assert\NotNull]
    #[ORM\Column(type: 'string')]
    private string $contentType;
    
    #[Assert\NotNull]
    #[ORM\Column(type: 'integer')]
    private int $contentSize;

    #[Assert\NotNull]
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $private = false;

    public function __construct()
    {
        $now = new DateTime('now');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }
    
    public function getFilename(): string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): self
    {
        $this->filename = $filename;
        return $this;
    }
    
    public function getContext(): string
    {
        return $this->context;
    }
    
    public function setContext(string $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }
    
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;
        return $this;
    }
    
    public function getContentSize(): int
    {
        return $this->contentSize;
    }

    public function setContentSize(int $contentSize): self
    {
        $this->contentSize = $contentSize;
        return $this;
    }

    public function isPrivate(): bool
    {
        return $this->private;
    }

    public function setPrivate(bool $private): Media
    {
        $this->private = $private;
        return $this;
    }
}
