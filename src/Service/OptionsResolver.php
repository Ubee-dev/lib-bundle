<?php

namespace UbeeDev\LibBundle\Service;

use BackedEnum;
use UbeeDev\LibBundle\Builder\ExpectationBuilder;
use UbeeDev\LibBundle\Config\CustomEnumInterface;
use UbeeDev\LibBundle\Config\ParameterType;
use UbeeDev\LibBundle\Entity\AbstractDateTime;
use UbeeDev\LibBundle\Entity\Date;
use UbeeDev\LibBundle\Entity\DateTime;
use UbeeDev\LibBundle\Exception\FileValidationException;
use UbeeDev\LibBundle\Exception\InvalidArgumentException;
use UbeeDev\LibBundle\Model\Type\Email;
use UbeeDev\LibBundle\Model\Type\Name;
use UbeeDev\LibBundle\Model\Type\PhoneNumber;
use UbeeDev\LibBundle\Model\Type\Url;
use DateTimeZone;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Money\Money;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OptionsResolver
{
    private array $parameters = [];
    private array $sanitizingExpectations = [];
    private array $allowedValues = [];
    private array $defaultValues = [];
    private bool $strictMode = true;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        return $this;
    }

    public function setSanitizingExpectations(array $sanitizingExpectations): self
    {
        // Normalize expectations to handle ParameterType enums
        $this->sanitizingExpectations = $this->normalizeExpectations($sanitizingExpectations);
        return $this;
    }

    public function setAllowedValues(array $allowedValues): self
    {
        $this->allowedValues = $allowedValues;
        return $this;
    }

    public function setDefaultValues(array $defaultValues): self
    {
        $this->defaultValues = $defaultValues;
        return $this;
    }

    public function setStrictMode(bool $strictMode): self
    {
        $this->strictMode = $strictMode;
        return $this;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function resolve(): array
    {
        $sanitizedParameters = $this->strictMode
            ? $this->removeExtraFields($this->parameters, $this->sanitizingExpectations)
            : $this->parameters;

        $errors = $this->processSanitizingExpectations($sanitizedParameters, $this->sanitizingExpectations);

        if (!empty($errors)) {
            throw new InvalidArgumentException(
                'Parameters fail the sanitizing expectations.',
                $errors,
                $this->parameters
            );
        }

        $this->checkAllowedValuesParameters($sanitizedParameters);

        return $sanitizedParameters;
    }

    private function normalizeExpectations(array $expectations): array
    {
        $normalized = [];
        foreach ($expectations as $key => $expectation) {
            $normalized[$key] = $this->normalizeExpectation($expectation);
        }
        return $normalized;
    }

    private function normalizeExpectation(mixed $expectation): array|string
    {
        if ($expectation instanceof ParameterType) {
            return ['type' => $expectation->value];
        }

        if ($expectation instanceof ExpectationBuilder) {
            // Convert ExpectationBuilder objects to array format
            return $this->normalizeExpectationArray($expectation->toArray());
        }

        if (is_array($expectation)) {
            return $this->normalizeExpectationArray($expectation);
        }

        return $expectation;
    }

    private function normalizeExpectationArray(array $expectation): array
    {
        // Normalize the type if it's a ParameterType enum
        if (isset($expectation['type']) && $expectation['type'] instanceof ParameterType) {
            $expectation['type'] = $expectation['type']->value;
        }

        // Recursively normalize items if they exist
        if (isset($expectation['items']) && is_array($expectation['items'])) {
            $expectation['items'] = $this->normalizeExpectations($expectation['items']);
        }

        return $expectation;
    }

    private function removeExtraFields(array $parameters, array $expectations): array
    {
        $filteredParameters = [];

        foreach ($expectations as $key => $expectation) {
            if (!array_key_exists($key, $parameters)) {
                continue;
            }

            $value = $parameters[$key];

            if (is_array($value) && isset($expectation['items'])) {
                // Handle nested arrays by filtering their content recursively
                if (array_is_list($value)) {
                    $filteredParameters[$key] = array_map(
                        fn($item) => is_array($item) ? $this->removeExtraFields($item, $expectation['items']) : $item,
                        $value
                    );
                } else {
                    $filteredParameters[$key] = $this->removeExtraFields($value, $expectation['items']);
                }
            } else {
                $filteredParameters[$key] = $value;
            }
        }

        return $filteredParameters;
    }

    private function processSanitizingExpectations(array &$parameters, array $expectations): array
    {
        $errors = [];

        foreach ($expectations as $key => $expectation) {

            $value = $parameters[$key] ?? null;

            if (is_string($value)) {
                $value = trim($value);
            }

            $defaultValue = $this->defaultValues[$key] ?? null;

            if (is_string($expectation)) {
                $expectation = ['type' => $expectation];
            }

            if (!isset($expectation['required'])) {
                $expectation['required'] = true;
            }

            if ($value === "" || $value === "null" || $value === null) {
                $parameters[$key] = $defaultValue ?? null;

                if($parameters[$key] === null && $expectation['type'] === 'array') {
                    $parameters[$key] = [];
                }

                if ($expectation['required'] && ($parameters[$key] === null || $parameters[$key] === [])) {
                    $errors[$key] = 'validation.required';
                }

                continue;
            }

            if ($expectation['type'] === 'array' && $expectation['required'] && is_array($value) && empty($value)) {
                $errors[$key] = 'validation.required';
                continue;
            }

            try {
                // Handle nested arrays with items
                if ($expectation['type'] === 'array' && isset($expectation['items']) && is_array($value)) {
                    if (array_is_list($value)) {
                        // List of objects: process each item
                        $nestedErrors = [];
                        foreach ($value as $index => $item) {
                            $itemErrors = $this->processSanitizingExpectations($item, $expectation['items']);
                            if (!empty($itemErrors)) {
                                $nestedErrors[$index] = $itemErrors;
                            }
                            $value[$index] = $item;
                        }
                        if (!empty($nestedErrors)) {
                            $errors[$key] = $nestedErrors;
                        }
                        $parameters[$key] = $value;
                    } else {
                        // Single associative object: process directly
                        $itemErrors = $this->processSanitizingExpectations($value, $expectation['items']);
                        if (!empty($itemErrors)) {
                            $errors[$key] = $itemErrors;
                        }
                        $parameters[$key] = $value;
                    }
                } else {
                    $parameters[$key] = $this->sanitizeParameter($key, $value, $expectation);

                    if ($parameters[$key] === null && $expectation['required'] && !isset($this->allowedValues[$key])) {
                        $errors[$key] = 'validation.invalid';
                    }
                }

            } catch (FileValidationException $e) {
                $errors[$key] = $e->getMessage();
            } catch (Exception|\ValueError) {
                $errors[$key] = 'validation.invalid';
            }
        }

        return $errors;
    }

    private function sanitizeParameter(string $key, mixed $value, array $expectation): mixed
    {
        $stripHtml = $expectation['stripHtml'] ?? true;

        if ($stripHtml) {
            $value = $this->removeHtmlTags($value);
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        switch ($expectation['type'] ?? null) {
            case 'int':
                return (int)$value;
            case 'float':
                return (float)str_replace(',', '.', $value);
            case 'bool':
                return $value === "true" || $value == '1';
            case 'date':
                return new Date($value);
            case 'datetime':
                return $this->getDateTimeValue($value);
            case 'money':
                return Money::EUR($value);
            case 'enum':
                return $this->getEnumValue($value, $expectation['class'] ?? null);
            case 'customEnum':
                return $this->getCustomEnumValue($value, $expectation['class'] ?? null);
            case 'email':
                return Email::from(trim($value));
            case 'name':
                return Name::from(trim($value));
            case 'url':
                return Url::from(trim($value));
            case 'phoneNumber':
                return PhoneNumber::from(trim($value));
            case 'entity':
                return $this->getEntity(
                    $value,
                    $expectation['keyParam'] ?? 'id',
                    $expectation['class'],
                    $expectation['extraParams'] ?? []
                );
            case 'array':
                if (!is_array($value)) {
                    throw new Exception("Invalid array format");
                }
                return $value;
            case 'file':
                return $this->validateFile($value, $expectation);
            case 'string':
                return trim($value);
            default:
                return $value;
        }
    }

    private function checkAllowedValuesParameters(array $parameters): void
    {
        $errors = [];

        foreach ($parameters as $parameterName => $parameter) {
            $allowedValues = $this->allowedValues[$parameterName] ?? null;

            //if allowed values parameter doesn't exist or parameter is allowed, do nothing
            if (!$allowedValues) {
                continue;
            }

            if (!is_array($allowedValues)) {
                $allowedValues = [$allowedValues];
            }

            if (in_array($parameter, $allowedValues, true)) {
                continue;
            }

            // cast parameter if needed
            if ($parameter instanceof BackedEnum) {
                $parameter = $parameter->value;
            }

            // cast allowed values if needed
            $allowedValues = array_map(function (mixed $allowedValue) {
                if ($allowedValue instanceof BackedEnum) {
                    return $allowedValue->value;
                }

                if (is_bool($allowedValue)) {
                    return $allowedValue ? 'true' : 'false';
                }

                return $allowedValue;
            }, $allowedValues);

            // convert current value to string if needed
            $currentValue = $this->parameters[$parameterName] ?? null;

            if (is_bool($currentValue)) {
                $currentValue = $currentValue ? 'true' : 'false';
            }

            $errors[$parameterName] = 'validation.not_allowed_value';
        }

        if ($errors) {
            throw new InvalidArgumentException('Parameters fail the sanitizing expectations.', $errors, $this->parameters);
        }
    }

    private function removeHtmlTags(mixed $value): UploadedFile|string|array|null
    {
        if (is_null($value)) {
            return null;
        }

        if ($value instanceof UploadedFile) {
            return $value;
        }

        return is_array($value)
            ? array_map([$this, 'removeHtmlTags'], $value)
            : strip_tags($value);
    }

    private function getEntity(mixed $keyValue, string $keyParam, string $className, array $extraParams = []): object|null
    {
        return $this->entityManager->getRepository($className)->findOneBy(array_merge([
            $keyParam => $keyValue
        ], $extraParams));
    }

    private function getDateTimeValue(mixed $parameter): DateTime
    {
        if ((string)((int)$parameter) == $parameter) {
            $parameter = (new DateTime())->setTimestamp($parameter);
        } else {
            $parameter = new DateTime($parameter);
        }

        $parameter->setTimezone(new DateTimeZone(AbstractDateTime::DEFAULT_TIMEZONE));

        return $parameter;
    }

    private function getEnumValue(mixed $parameter, string $class): ?BackedEnum
    {
        $enumType = $class;
        return $enumType::tryFrom(trim($parameter));
    }

    private function getCustomEnumValue(mixed $parameter, string $class): ?CustomEnumInterface
    {
        $enumType = $class;
        return $enumType::tryFrom(trim($parameter));
    }

    private function validateFile(mixed $value, array $expectation): UploadedFile
    {
        if (!$value instanceof UploadedFile) {
            throw new Exception("Invalid file format");
        }

        $allowedExtensions = $expectation['extensions'] ?? null;
        $allowedMimetypes = $expectation['mimetypes'] ?? null;

        if ($allowedExtensions !== null) {
            $extension = strtolower($value->getClientOriginalExtension());
            // Normalize extensions by removing leading dots and lowercasing
            $normalizedExtensions = array_map(fn($e) => strtolower(ltrim($e, '.')), $allowedExtensions);

            if (!in_array($extension, $normalizedExtensions, true)) {
                throw new FileValidationException('validation.file.extension_invalid');
            }
        }

        if ($allowedMimetypes !== null) {
            $mimetype = $value->getClientMimeType();

            if (!in_array($mimetype, $allowedMimetypes, true)) {
                throw new FileValidationException('validation.file.mime_type_invalid');
            }
        }

        return $value;
    }
}
