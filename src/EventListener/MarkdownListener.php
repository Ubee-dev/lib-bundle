<?php

namespace UbeeDev\LibBundle\EventListener;

use UbeeDev\LibBundle\Annotations\Markdown;
use UbeeDev\LibBundle\Model\MarkdownInterface;
use UbeeDev\LibBundle\Service\MarkdownParserInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Events;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Exception;
use ReflectionClass;
use ReflectionException;
use ReflectionProperty;

#[AsDoctrineListener(event: Events::prePersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::preUpdate, priority: 500, connection: 'default')]
readonly class MarkdownListener
{
    public function __construct(
        private MarkdownParserInterface $markdownParser
    ) {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
            Events::preUpdate,
        ];
    }


    /**
     * @param PrePersistEventArgs $event
     * @throws ReflectionException
     */
    public function prePersist(PrePersistEventArgs $event): void
    {
        $this->parseMarkdownFields($event->getObject());
    }

    /**
     * @param PreUpdateEventArgs $event
     * @throws ReflectionException
     */
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $this->parseMarkdownFields($event->getObject());
    }

    /**
     * @param object $entity
     * @throws ReflectionException
     * @throws Exception
     */
    private function parseMarkdownFields(object $entity): void
    {
        if ($entity instanceof MarkdownInterface) {
            $properties = $this->getClassProperties($entity);
            foreach ($properties as $property) {
                $attributes = $property->getAttributes();
                foreach ($attributes as $attribute) {
                    if ($attribute->getName() === Markdown::class) {
                        $getMethod = 'get' . ucfirst($property->getName());
                        $setMethod = 'set' . ucfirst($attribute->getArguments()['mappedProperty']);
                        $entity->$setMethod($this->markdownParser->parse($entity->$getMethod()));
                    }
                }
            }
        }
    }

    /**
     * @param MarkdownInterface $entity
     * @return ReflectionProperty[]
     * @throws ReflectionException
     */
    private function getClassProperties(MarkdownInterface $entity): array
    {
        $properties = [];
        $reflectionClass = new ReflectionClass($entity);

        do {
            $properties = array_merge($properties, $reflectionClass->getProperties());
            $reflectionClass = $reflectionClass->getParentClass();
        } while ($reflectionClass !== false);

        return $properties;
    }
}
