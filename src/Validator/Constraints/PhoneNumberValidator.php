<?php

namespace Khalil1608\LibBundle\Validator\Constraints;

use Khalil1608\LibBundle\Model\PhoneNumberInterface;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber as PhoneNumberObject;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

/**
 * Phone number validator.
 */
class PhoneNumberValidator extends ConstraintValidator
{
    private string $errorPath = 'phoneNumber';

    /**
     * @param PhoneNumberInterface|string|null $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if(!$value) {
            return;
        }

        $entity = $value;

        if(!$value instanceof PhoneNumberInterface) {
            $entity = $this->context->getRoot();
        }

        $phoneNumber = $entity->getPhoneNumber();
        $countryCallingCode = $entity->getCountryCallingCode();

        if($constraint->errorPath) {
            $this->errorPath = $constraint->errorPath;
        }
        if (null === $phoneNumber || '' === $phoneNumber) {
            return;
        }
        if (!is_scalar($phoneNumber) && !(is_object($phoneNumber) && method_exists($phoneNumber, '__toString'))) {
            throw new UnexpectedTypeException($phoneNumber, 'string');
        }

        $phoneNumber = '+'.$countryCallingCode.$phoneNumber;

        $phoneUtil = PhoneNumberUtil::getInstance();
        if (false === $phoneNumber instanceof PhoneNumberObject) {
            $phoneNumber = (string) $phoneNumber;
            try {
                $phoneNumber = $phoneUtil->parse($phoneNumber, $constraint->defaultRegion);
            } catch (NumberParseException $e) {
                $this->addViolation($phoneNumber, $constraint);
                return;
            }
        } else {
            $phoneNumber = $phoneNumber;
            $phoneNumber = $phoneUtil->format($phoneNumber, PhoneNumberFormat::NATIONAL);
        }

        if (false === $phoneUtil->isValidNumber($phoneNumber)) {

            $this->addViolation($phoneNumber, $constraint);
            return;
        }
        $validTypes = match ($constraint->getType()) {
            PhoneNumber::FIXED_LINE => array(PhoneNumberType::FIXED_LINE, PhoneNumberType::FIXED_LINE_OR_MOBILE),
            PhoneNumber::MOBILE => array(PhoneNumberType::MOBILE, PhoneNumberType::FIXED_LINE_OR_MOBILE),
            PhoneNumber::PAGER => array(PhoneNumberType::PAGER),
            PhoneNumber::PERSONAL_NUMBER => array(PhoneNumberType::PERSONAL_NUMBER),
            PhoneNumber::PREMIUM_RATE => array(PhoneNumberType::PREMIUM_RATE),
            PhoneNumber::SHARED_COST => array(PhoneNumberType::SHARED_COST),
            PhoneNumber::TOLL_FREE => array(PhoneNumberType::TOLL_FREE),
            PhoneNumber::UAN => array(PhoneNumberType::UAN),
            PhoneNumber::VOIP => array(PhoneNumberType::VOIP),
            PhoneNumber::VOICEMAIL => array(PhoneNumberType::VOICEMAIL),
            default => array(),
        };

        if (count($validTypes)) {
            $type = $phoneUtil->getNumberType($phoneNumber);
            if (false === in_array($type, $validTypes)) {
                $this->addViolation($phoneNumber, $constraint);
                return;
            }
        }
    }
    /**
     * Add a violation.
     *
     * @param mixed      $value      The value that should be validated.
     * @param Constraint $constraint The constraint for the validation.
     */
    private function addViolation($value, Constraint $constraint)
    {
        /** @var PhoneNumber $constraint */
        if ($this->context instanceof ExecutionContextInterface) {
            $this->context->buildViolation($constraint->getMessage())
                ->setParameter('{{ type }}', $constraint->getType())
                ->setParameter('{{ value }}', $this->formatValue($value))
                ->setCode(PhoneNumber::INVALID_PHONE_NUMBER_ERROR)
                ->atPath($this->errorPath)
                ->addViolation();
        } else {
            $this->context->addViolation($constraint->getMessage(), array(
                '{{ type }}' => $constraint->getType(),
                '{{ value }}' => $value
            ));
        }
    }
}
