<?php

namespace UbeeDev\LibBundle\Service;

class TextParser
{

    public function parse(string $text): string
    {
        return str_replace('\n', '<br>', $text);
    }
}
