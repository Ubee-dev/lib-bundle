<?php

namespace Khalil1608\LibBundle\Validator\Constraints;

use App\Entity\Media;
use Exception;
use LogicException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use function strlen;
use const PATHINFO_EXTENSION;

class FileValidator extends ConstraintValidator
{
    public const KB_BYTES = 1000;
    public const MB_BYTES = 1000000;
    public const KIB_BYTES = 1024;
    public const MIB_BYTES = 1048576;

    private const SUFFICES = [
        1 => 'bytes',
        self::KB_BYTES => 'kB',
        self::MB_BYTES => 'MB',
        self::KIB_BYTES => 'KiB',
        self::MIB_BYTES => 'MiB',
    ];

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof File) {
            throw new UnexpectedTypeException($constraint, File::class);
        }

        // custom constraints should ignore null values to allow
        // other constraints (NotBlank, NotNull, etc.) to take care of that
        // return if media doesn't have restriction on file
        if (is_null($value) || $value === '') {
            return;
        }

        if (!$value instanceof Media) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, Media::class);
        }

        $this->checkExtension($value, $constraint);
        $this->checkMimeType($value, $constraint);
        $this->checkSize($value, $constraint);
    }

    public function checkSize(Media $media, File $constraint): void
    {
        $iniLimitSize = UploadedFile::getMaxFilesize();
        if ($constraint->maxSize && $constraint->maxSize < $iniLimitSize) {
            $limitInBytes = $constraint->maxSize;
            $binaryFormat = $constraint->binaryFormat;
        } else {
            $limitInBytes = $iniLimitSize;
            $binaryFormat = $constraint->binaryFormat ?? true;
        }


        $mediaSize = $media->getContentSize();

        if ($mediaSize > $limitInBytes) {
            [, $limitAsString, $suffix] = $this->factorizeSizes(0, $limitInBytes, $binaryFormat);

            $this->context->buildViolation($constraint->maxSizeMessage)
                ->setParameter('{{ limit }}', $limitAsString)
                ->setParameter('{{ suffix }}', $suffix)
                ->setParameter('{{ size }}', $this->convertToUnit($mediaSize, $suffix))
                ->addViolation();
        }
    }

    private function checkMimeType(Media $media, File $constraint): void
    {
        $mime = $media->getContentType();
        $mimeTypes = $constraint->mimeTypes;

        // check if content type matches allowed contentType
        $isMatching = array_filter($mimeTypes, function ($v) use ($media) {
            return preg_match('~' . $v . '~', $media->getContentType());
        });

        if (!$isMatching) {
            $this->context->buildViolation($constraint->mimeTypesMessage)
                ->setParameter('{{ file }}', $this->formatValue($media->getFilename()))
                ->setParameter('{{ type }}', $this->formatValue($mime))
                ->setParameter('{{ types }}', $this->formatValues($mimeTypes))
                ->addViolation();
        }
    }

    private function checkExtension(Media $media, File $constraint): void
    {
        $fileExtension = pathinfo($media->getFilename(), PATHINFO_EXTENSION);
        $mediaMimeType = $media->getContentType();

        $mimeTypesHelper = MimeTypes::getDefault();

        $extensionFound = false;

        foreach ($constraint->extensions as $extension) {
            $mimeTypes = $mimeTypesHelper->getMimeTypes($extension);

            // if file extension doesn't match allowed extension
            if ($fileExtension !== $extension) {
                continue;
            }

            $mimeTypes = $constraint->mimeTypes ? array_intersect($mimeTypes, $constraint->mimeTypes) : $mimeTypes;

            // if media mime type doesn't match allowed mime types
            if (!in_array($mediaMimeType, $mimeTypes)) {
                continue;
            }

            $extensionFound = true;
            break;
        }

        if (!$extensionFound) {
            $this->context->buildViolation($constraint->extensionsMessage)
                ->setParameter('{{ extension }}', $this->formatValue($fileExtension))
                ->setParameter('{{ extensions }}', $this->formatValues($constraint->extensions))
                ->addViolation();
        }
    }

    private static function moreDecimalsThan(string $double, int $numberOfDecimals): bool
    {
        return strlen($double) > strlen(round($double, $numberOfDecimals));
    }

    /**
     * Convert the limit to the smallest possible number
     * (i.e. try "MB", then "kB", then "bytes").
     */
    private function factorizeSizes(int $size, int|float $limit, bool $binaryFormat): array
    {
        if ($binaryFormat) {
            $coef = self::MIB_BYTES;
            $coefFactor = self::KIB_BYTES;
        } else {
            $coef = self::MB_BYTES;
            $coefFactor = self::KB_BYTES;
        }

        // If $limit < $coef, $limitAsString could be < 1 with less than 3 decimals.
        // In this case, we would end up displaying an allowed size < 1 (eg: 0.1 MB).
        // It looks better to keep on factorizing (to display 100 kB for example).
        while ($limit < $coef) {
            $coef /= $coefFactor;
        }

        $limitAsString = (string) ($limit / $coef);

        // Restrict the limit to 2 decimals (without rounding! we
        // need the precise value)
        while (self::moreDecimalsThan($limitAsString, 2)) {
            $coef /= $coefFactor;
            $limitAsString = (string) ($limit / $coef);
        }

        // Convert size to the same measure, but round to 2 decimals
        $sizeAsString = (string) round($size / $coef, 2);

        // If the size and limit produce the same string output
        // (due to rounding), reduce the coefficient
        while ($sizeAsString === $limitAsString) {
            $coef /= $coefFactor;
            $limitAsString = (string) ($limit / $coef);
            $sizeAsString = (string) round($size / $coef, 2);
        }

        return [$sizeAsString, $limitAsString, self::SUFFICES[$coef]];
    }

    /**
     * @throws Exception
     */
    public function convertToUnit(int $size, string $unit): string
    {
        if ($unit == "KB") {
            return round($size / 1024, 1);
        }
        if ($unit == "MB") {
            return round($size / 1024 / 1024, 1);
        }
        if ($unit == "GB") {
            return round($size / 1024 / 1024 / 1024, 1);
        }

        throw new Exception('Invalid unit '.$unit);
    }
}
