<?php

namespace Khalil1608\LibBundle\Config;

interface CustomEnumInterface {

    public static function from(mixed $value): self;
    public static function tryFrom(mixed $value): ?self;
}
