<?php

namespace UbeeDev\LibBundle\Service\Slack;

class FileSnippet extends AbstractSnippet
{
    public function __construct(
        private readonly string $filePath
    ) {
        if (!file_exists($this->filePath)) {
            throw new \InvalidArgumentException("File does not exist: {$this->filePath}");
        }
        parent::__construct($this->filePath);
    }

    public function getFileName(): string
    {
        return basename($this->filePath);
    }

    public function getSnippetType(): ?string
    {
        return $this->getSnippetTypeFromExtension($this->getFileName());
    }

    public function getContent(): string
    {
        return file_get_contents($this->filePath);
    }
    
    private function getSnippetTypeFromExtension(string $filename): ?string
    {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($extension) {
            'js' => 'javascript',
            'json' => 'json',
            'php' => 'php',
            'py' => 'python',
            'xml' => 'xml',
            'yml', 'yaml' => 'yaml',
            'html', 'htm' => 'html',
            'css' => 'css',
            'sql' => 'sql',
            'log' => 'text',
            'csv' => 'csv',
            default => null,
        };
    }
}
