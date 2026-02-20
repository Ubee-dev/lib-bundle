<?php

namespace UbeeDev\LibBundle\Service\Slack;

abstract class AbstractSnippet implements SlackSnippetInterface
{
    protected mixed $content;
    
    public function __construct(mixed $content)
    {
        $this->content = $content;
    }

    abstract function getFileName(): string;
    abstract function getSnippetType(): ?string;
    abstract function getContent(): string;
    
    public function jsonSerialize(): array
    {
        return [
            'content' => $this->content,
            'class' => static::class,
        ];
    }
}