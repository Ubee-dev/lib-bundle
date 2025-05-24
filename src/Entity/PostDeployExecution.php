<?php

namespace Khalil1608\LibBundle\Entity;

use Khalil1608\LibBundle\Repository\PostDeployExecutionRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[UniqueEntity(
    fields: ['name'],
    errorPath: 'name'
)]
#[ORM\Entity(repositoryClass: PostDeployExecutionRepository::class)]
class PostDeployExecution
{
    #[ORM\Column(type: "integer")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;

    #[Assert\NotBlank]
    #[ORM\Column(type: 'string', unique: true)]
    protected ?string $name = null;

    #[Assert\NotNull]
    #[ORM\Column(type: 'datetime')]
    protected ?DateTime $executedAt = null;

    #[Assert\NotNull]
    #[ORM\Column(type: 'integer')]
    protected ?int $executionTime = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): PostDeployExecution
    {
        $this->name = $name;
        return $this;
    }

    public function getExecutedAt(): ?DateTime
    {
        return $this->executedAt;
    }

    public function setExecutedAt(DateTime $executedAt): self
    {
        $this->executedAt = $executedAt;
        return $this;
    }

    public function getExecutionTime(): ?int
    {
        return $this->executionTime;
    }

    public function setExecutionTime(int $executionTime): self
    {
        $this->executionTime = $executionTime;
        return $this;
    }
}