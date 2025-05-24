<?php

namespace Khalil1608\LibBundle\Service;

interface MarkdownParserInterface
{
    public function parse(?string $markdown, bool $fullParsing = true): ?string;
}