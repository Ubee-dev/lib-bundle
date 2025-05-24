<?php

namespace Khalil1608\LibBundle\Serializer;

use Khalil1608\LibBundle\Entity\Date;
use Khalil1608\LibBundle\Entity\DateTime;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;

class DateTimeNormalizer implements NormalizerInterface
{
    /**
     * @param Date|DateTime $object
     * @param string|null $format
     * @param array $context
     * @return string
     */
    public function normalize($object, string $format = null, array $context = []): string
    {
        return $object->jsonSerialize();
    }

    public function supportsNormalization($data, string $format = null, array $context = []): bool
    {
        return $data instanceof Date || $data instanceof DateTime;
    }

    public function getSupportedTypes(?string $format): array
    {
        return [
            Date::class => true,
            DateTime::class => true,
        ];
    }
}