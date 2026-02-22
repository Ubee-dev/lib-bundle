<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Entity\Media;
use UbeeDev\LibBundle\Entity\PostDeployExecution;
use UbeeDev\LibBundle\Traits\DateTimeTrait;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Faker\Generator;
use JetBrains\PhpStorm\ArrayShape;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpKernel\KernelInterface;

class Factory implements FactoryInterface
{
    const UPLOAD_CONTEXT = 'tests';

    use RandomTrait;
    use DateTimeTrait;

    /** @var EntityManager */
    protected $em;

    /** @var KernelInterface */
    protected KernelInterface $kernel;

    /** @var Generator */
    protected $faker;

    protected ParameterBagInterface $parameter;

    public function __construct(EntityManagerInterface $em, KernelInterface $kernel, ParameterBagInterface $parameter)
    {
        $this->em = $em;
        $this->kernel = $kernel;
        $this->faker = \Faker\Factory::create("fr_FR");
        $this->parameter = $parameter;
    }

    public function createPostDeployCommand(
        #[ArrayShape([
            'name' => "string",
            'executionTime' => "string",
            'executedAt' => DateTime::class,
        ])]
        array $properties = []
    ): PostDeployExecution
    {
        return $this->createEntity('PostDeployCommand', $properties);
    }

    public function buildPostDeployCommand(
        #[ArrayShape([
            'name' => "string",
            'executionTime' => "string",
            'executedAt' => DateTime::class,
        ])]
        array $properties = [],
        bool $isCreation = false
    ): PostDeployExecution
    {
        $defaultProperties = [
            'name' => $this->dateTime()->format('YmdHis').ucfirst($this->faker->words(1)[0]),
            'executionTime' => 10,
            'executedAt' => $this->dateTime('-2 days'),
        ];

        return $this->buildEntity(new PostDeployExecution(), $properties, $defaultProperties);
    }

    public function setEntityManager(EntityManager $entityManager)
    {
        return $this->em = $entityManager;
    }

    public function setKernel(KernelInterface $kernel)
    {
        $this->kernel = $kernel;
    }
    
    protected function createEntity($class, array $properties = [])
    {
        $method = 'build' . $class;
        $entity = $this->$method($properties, true);
        $this->em->persist($entity);
        $this->em->flush();
        return $entity;
    }

    public function buildEntity($entity, $properties, $defaultProperties): mixed
    {
        $properties = array_merge($defaultProperties, $properties);
        $associationMappings = $this->em->getClassMetadata($entity::class)->getAssociationMappings();
        foreach ($properties as $key => $property) {

            if(
                $property
                && array_key_exists($key, $associationMappings)
                && $this->isNotAnInstanceOfTargetEntity($associationMappings[$key], $property)
                && $associationMappings[$key] instanceof ManyToOneAssociationMapping
            ) {
                $className = explode('\\', $associationMappings[$key]['targetEntity']);
                $className = end($className);
                $parentClassName = explode('\\', $entity::class);
                $parentClassName = end($parentClassName);
                if(method_exists($associationMappings[$key]->targetEntity, 'set'.$parentClassName)) {
                    $property = array_merge($property, [strtolower($parentClassName) => $entity]);
                }
                $method = 'build' . $className;
                $property = $this->$method($property);
            }

            $setMethod = 'set' . ucfirst($key);
            $addMethod = 'add' . rtrim(ucfirst($key), 's');

            if (method_exists($entity, $setMethod)) {
                // If set method exists, call it
                $entity->$setMethod($property);
            } elseif (method_exists($entity, $addMethod)) {
                foreach ($property as $p) {
                    // If add method exists, call it
                    $entity->$addMethod($p);
                }

            } else {
                throw new \Exception('Method for property ' . $key . ' not found');
            }
        }
        return $entity;
    }

    /**
     * @param $ressource
     * @param bool $isCreation
     * @param array $additionnalProperties
     * @return mixed
     */
    public function buildOrCreate($ressource, $isCreation = false, $additionnalProperties = [])
    {
        $method = $isCreation ? 'create' : 'build';
        $method .= ucfirst($ressource);

        return $this->$method($additionnalProperties, $isCreation);
    }


    public function buildMedia(array $properties = [])
    {
        $filename = $properties['filename'] ?? $this->randomName(true) . '.pdf';
        $context = $properties['context'] ?? self::UPLOAD_CONTEXT;

        $defaultProperties = [
            'filename' => $filename,
            'context' => $context,
            'contentType' => 'application/pdf',
            'contentSize' => 3000,
            'storagePath' => 'uploads/' . $context . '/' . (new DateTime('now'))->format('Ym') . '/' . $filename,
        ];

        $mediaClassName = $this->parameter->get('mediaClassName');
        return $this->buildEntity(new $mediaClassName(), $properties, $defaultProperties);
    }

    public function createMedia(array $properties = []): Media
    {
        return $this->createEntity('Media', $properties);
    }

    public function generateCharacters(int $nbCharacters): string
    {
        // Generate a paragraph
        $paragraph = $this->faker->paragraph($nbCharacters);

        // Adjust the length of the text to $nbCharacters characters
        return substr($paragraph, 0, $nbCharacters);
    }

    /**
     * @throws Exception
     */
    public function generateUniqueNumber($nbCharacters): int
    {
        $min = 10 ** ($nbCharacters - 1); // Minimum value for the unique number
        $max = (10 ** $nbCharacters) - 1; // Maximum value for the unique number

        return random_int($min, $max); // Generate a random number within the specified range
    }

    private function isNotAnInstanceOfTargetEntity($associationMapping, mixed $property): bool
    {
        return !$property instanceof $associationMapping['targetEntity'];
    }
}
