<?php

namespace  UbeeDev\LibBundle\Validator\Constraints;



use UbeeDev\LibBundle\Traits\DateTimeTrait;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class DateStartDuringGivenDayValidator extends ConstraintValidator
{
    use DateTimeTrait;
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof DateStartDuringGivenDay) {
            throw new UnexpectedTypeException($constraint, DateStartDuringGivenDay::class);
        }

        if (!$constraint->propertyPath) {
            throw new ConstraintDefinitionException(sprintf('The "%s" constraint requires the "propertyPath" option to be set.', static::class));
        }

        if (null === $value || '' === $value) {
            return;
        }

        $entity = $this->context->getRoot();
        $method = 'get'.ucfirst($constraint->propertyPath);

        if(!method_exists($entity, $method)) {
            throw new ConstraintDefinitionException(sprintf('Undefined method "%s". This method shoud be implemented', $method));
        }

        $date2 = $entity->$method();

        if(!$constraint->includeMidnight && !$this->dateStartDuringGivenDay($value, $date2)) {
            $this->context->buildViolation($constraint->message)
                ->atPath($this->context->getPropertyName())
                ->addViolation();
        }

        if($constraint->includeMidnight && !$this->dateStartDuringGivenDay($value, $date2) && $date2->format('H:i:s') !== '00:00:00') {
            $this->context->buildViolation($constraint->message)
                ->atPath($this->context->getPropertyName())
                ->addViolation();
        }

    }
}
