<?php

namespace UbeeDev\LibBundle\Service\Slack;

class TextSnippet extends AbstractSnippet
{
    public function getFileName(): string
    {
        return 'text';
    }

    public function getSnippetType(): ?string
    {
        return 'text';
    }

    public function getContent(): string
    {
        return $this->content;
    }
}