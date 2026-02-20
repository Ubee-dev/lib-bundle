<?php

namespace UbeeDev\LibBundle\Service\Slack;

class JsonSnippet extends AbstractSnippet
{
    public function getFileName(): string
    {
        return 'data';
    }

    public function getSnippetType(): ?string
    {
        return 'json';
    }

    public function getContent(): string
    {
        return json_encode($this->content, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}