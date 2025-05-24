<?php

namespace Khalil1608\LibBundle\Validator\Constraints;

use Symfony\Component\Validator\Attribute\HasNamedArguments;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;

#[\Attribute]
class File extends Constraint
{
    /**
     * @param array<string, string|string[]>|string[]|string $extensions
     */
    public function __construct(
        public string|int|null $maxSize = null,
        public ?bool $binaryFormat = null,
        public array $mimeTypes = [],
        public string $maxSizeMessage = 'Khalil1608_lib.media.max_size.invalid',
        public string $mimeTypesMessage = 'Khalil1608_lib.media.mime_type.invalid',
        public array $extensions = [],
        public string $extensionsMessage = 'Khalil1608_lib.media.extension.invalid',
    ) {
        parent::__construct();
        $this->maxSize = $maxSize ?? $this->maxSize;
        $this->mimeTypes = $mimeTypes ?? $this->mimeTypes;
        $this->extensions = $extensions ?? $this->extensions;
        $this->maxSizeMessage = $maxSizeMessage ?? $this->maxSizeMessage;
        $this->mimeTypesMessage = $mimeTypesMessage ?? $this->mimeTypesMessage;
        $this->extensionsMessage = $extensionsMessage ?? $this->extensionsMessage;

        if (null !== $this->maxSize) {
            $this->normalizeBinaryFormat($this->maxSize);
        }
    }

    private function normalizeBinaryFormat(int|string $maxSize): void
    {
        $factors = [
            'k' => 1000,
            'ki' => 1 << 10,
            'm' => 1000 * 1000,
            'mi' => 1 << 20,
            'g' => 1000 * 1000 * 1000,
            'gi' => 1 << 30,
        ];
        if (ctype_digit((string) $maxSize)) {
            $this->maxSize = (int) $maxSize;
            $this->binaryFormat ??= false;
        } elseif (preg_match('/^(\d++)('.implode('|', array_keys($factors)).')$/i', $maxSize, $matches)) {
            $this->maxSize = $matches[1] * $factors[$unit = strtolower($matches[2])];
            $this->binaryFormat ??= 2 === \strlen($unit);
        } else {
            throw new ConstraintDefinitionException(sprintf('"%s" is not a valid maximum size.', $maxSize));
        }
    }
}
