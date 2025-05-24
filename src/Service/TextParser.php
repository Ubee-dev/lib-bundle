<?php

namespace Khalil1608\LibBundle\Service;

class TextParser
{

    public function parse($text)
    {
        return str_replace('\n', '<br>', $text);
    }
}
