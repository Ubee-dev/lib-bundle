<?php

namespace UbeeDev\LibBundle\Config;

interface CustomEnumInterface {

    public static function from(mixed $value): self;
    public static function tryFrom(mixed $value): ?self;
}
