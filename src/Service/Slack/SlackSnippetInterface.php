<?php

namespace UbeeDev\LibBundle\Service\Slack;

interface SlackSnippetInterface extends \JsonSerializable
{
    public function getFileName(): string;
    public function getSnippetType(): ?string;
    public function getContent(): string;
}