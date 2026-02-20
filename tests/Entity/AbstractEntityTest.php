<?php


namespace UbeeDev\LibBundle\Tests\Entity;


use UbeeDev\LibBundle\Entity\AbstractEntity;
use UbeeDev\LibBundle\Tests\AbstractWebTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Exception;

class AbstractEntityTest extends AbstractWebTestCase
{
    /**
     * @throws Exception
     */
    public function testGetCollectionSortBy()
    {
        $dummyClass = $this->createDummyClass();

        $dummyClass->addCollection($subDummyClass1 = $this->createDummyClass($this->dateTime('+4 days')));
        $dummyClass->addCollection($subDummyClass2 = $this->createDummyClass($this->dateTime('+2 days')));
        $dummyClass->addCollection($subDummyClass3 = $this->createDummyClass($this->dateTime('-8 days')));

        $collection = $dummyClass->getCollectionSortedBy('collection', 'createdAt');

        $this->assertEquals($subDummyClass3, $collection[0]);
        $this->assertEquals($subDummyClass2, $collection[1]);
        $this->assertEquals($subDummyClass1, $collection[2]);

        $collection = $dummyClass->getCollectionSortedBy('collection', 'createdAt', 'DESC');

        $this->assertEquals($subDummyClass1, $collection[0]);
        $this->assertEquals($subDummyClass2, $collection[1]);
        $this->assertEquals($subDummyClass3, $collection[2]);
    }

    private function createDummyClass($createdAt = null): AbstractEntity
    {
        return new class($createdAt) extends AbstractEntity {

            protected ArrayCollection $collection;

            public function __construct($createdAt)
            {
                $this->createdAt = $createdAt;
                $this->collection = new ArrayCollection();
            }

            public function getCollection(): ArrayCollection
            {
                return $this->collection;
            }

            public function addCollection($collection)
            {
                $this->collection->add($collection);
            }
        };
    }
}
