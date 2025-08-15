<?php

namespace Khalil1608\LibBundle\Entity;

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

    // Nouveaux champs pour l'accessibilité et le référencement
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $alt = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $title = null;

    public function __construct()
    {
        $now = new DateTime('now');
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    // Getters et setters existants...
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

    public function setPrivate(bool $private): self
    {
        $this->private = $private;
        return $this;
    }

    // Nouveaux getters et setters pour les métadonnées
    public function getAlt(): ?string
    {
        return $this->alt;
    }

    public function setAlt(?string $alt): self
    {
        $this->alt = $alt;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Retourne l'alt text ou un fallback basé sur le nom du fichier
     */
    public function getAltOrFallback(): string
    {
        if ($this->alt) {
            return $this->alt;
        }

        // Fallback : utiliser le nom du fichier sans extension
        $pathInfo = pathinfo($this->filename);
        return ucfirst(str_replace(['_', '-'], ' ', $pathInfo['filename']));
    }

    /**
     * Vérifie si c'est une image
     */
    public function isImage(): bool
    {
        return str_starts_with($this->contentType, 'image/');
    }
}