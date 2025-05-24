<?php

namespace Khalil1608\LibBundle\Model;

interface JsonSerializable
{
    public function jsonSerialize(array $params = []);
}