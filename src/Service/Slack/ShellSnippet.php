<?php

namespace UbeeDev\LibBundle\Service\Slack;

class ShellSnippet extends AbstractSnippet
{
    public function getFileName(): string
    {
        return 'command';
    }

    public function getSnippetType(): ?string
    {
        return 'shell';
    }

    public function getContent(): string
    {
        return $this->content;
    }
}