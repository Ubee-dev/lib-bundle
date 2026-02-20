<?php

namespace UbeeDev\LibBundle\Model;

interface JsonSerializable
{
    public function jsonSerialize(array $params = []);
}