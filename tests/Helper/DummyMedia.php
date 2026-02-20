<?php

namespace UbeeDev\LibBundle\Tests\Helper;

use Doctrine\ORM\Mapping as ORM;
use UbeeDev\LibBundle\Entity\Media;

#[ORM\Entity]
#[ORM\Table(name: 'media')]
class DummyMedia extends Media
{
}
