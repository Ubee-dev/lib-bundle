<?php

namespace UbeeDev\LibBundle\EventListener;

use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Service\MediaManager;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postRemove)]
class MediaListener implements EventSubscriber
{
    private MediaManager $fileManager;

    public function __construct(MediaManager $fileManager)
    {
        $this->fileManager = $fileManager;
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postRemove,
        ];
    }

    public function postRemove(PostRemoveEventArgs $event): void
    {
        $this->removeFile($event->getObject());
    }

    private function removeFile($entity): void
    {
        /** @var Media $entity */
        if ($entity instanceof Media) {
            $this->fileManager->deleteAsset($entity);
        }
    }
}
