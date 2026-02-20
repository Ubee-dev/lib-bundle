<?php

namespace UbeeDev\LibBundle\Service;

interface MarkdownParserInterface
{
    public function parse(?string $markdown, bool $fullParsing = true): ?string;
}