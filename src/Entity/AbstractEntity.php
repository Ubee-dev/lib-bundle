<?php

namespace Khalil1608\LibBundle\Entity;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\MappedSuperclass;
use JetBrains\PhpStorm\ArrayShape;

#[MappedSuperclass]
#[ORM\HasLifecycleCallbacks()]
abstract class AbstractEntity
{
    #[ORM\Column(type: "integer")]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    protected ?int $id = null;
    
    #[ORM\Column(type: 'datetime')]
    protected ?DateTime $createdAt = null;

    #[ORM\Column(type: 'datetime')]
    protected ?DateTime $updatedAt = null;

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateTimestamps(): void
    {
        $this->setUpdatedAt(new DateTime('now'));

        if (!$this->getCreatedAt()) {
            $this->setCreatedAt(new DateTime('now'));
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }


    public function setCreatedAt(DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
    
    public function getCollectionSortedBy($collectionName, $sort, string $sortType = Criteria::ASC): array
    {
        $methodCollection = 'get'.ucfirst($collectionName);
        $collection = $this->$methodCollection()->toArray();

        $sort = is_array($sort) ? $sort : [$sort];
        usort($collection, function ($a, $b) use ($sort, $sortType) {

            foreach ($sort as $s) {
                $method = 'get'.ucfirst($s);
                $firstValue = $a->$method();
                $secondValue = $b->$method();

                if ($firstValue === $secondValue) {
                    continue;
                } else if ($firstValue < $secondValue && $sortType === Criteria::DESC)
                    return 1;
                else if ($firstValue > $secondValue && $sortType === Criteria::ASC)
                    return 1;
                else
                    return -1;
            }
        });

        return $collection;
    }
    
    #[ArrayShape([
        'resourceId' => "int|null",
        'primaryLabel' => "string",
        'secondaryLabel' => "mixed|null",
        'isDeletable' => "bool"
    ])]
    protected function _getItem(string $primaryLabel, mixed $secondaryLabel = null, ?bool $isDeletable = null): array
    {
        $item = [
            'resourceId' => $this->getId(),
            'primaryLabel' => $primaryLabel,
            'secondaryLabel' => $secondaryLabel,
        ];

        if($isDeletable !== null) {
            $item['isDeletable'] = $isDeletable;
        }

        return $item;
    }
}
