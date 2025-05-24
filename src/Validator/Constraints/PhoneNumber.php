<?php
namespace Khalil1608\LibBundle\Validator\Constraints;

use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PhoneNumber extends Constraint
{
    const ANY = 'any';
    const FIXED_LINE = 'fixed_line';
    const MOBILE = 'mobile';
    const PAGER = 'pager';
    const PERSONAL_NUMBER = 'personal_number';
    const PREMIUM_RATE = 'premium_rate';
    const SHARED_COST = 'shared_cost';
    const TOLL_FREE = 'toll_free';
    const UAN = 'uan';
    const VOIP = 'voip';
    const VOICEMAIL = 'voicemail';
    const INVALID_PHONE_NUMBER_ERROR = 'ca23f4ca-38f4-4325-9bcc-eb570a4abe7f';
    const ERROR_NAMES = [
        self::INVALID_PHONE_NUMBER_ERROR => 'INVALID_PHONE_NUMBER_ERROR',
    ];
    public ?string $message = null;
    public string $type = self::ANY;
    public string $defaultRegion = PhoneNumberUtil::UNKNOWN_REGION;

    public function __construct(public string $errorPath = 'phoneNumber')
    {
        parent::__construct([]);
    }


    public function getType(): string
    {
        return match ($this->type) {
            self::FIXED_LINE, self::MOBILE, self::PAGER, self::PERSONAL_NUMBER, self::PREMIUM_RATE, self::SHARED_COST, self::TOLL_FREE, self::UAN, self::VOIP, self::VOICEMAIL => $this->type,
            default => self::ANY,
        };
    }
    public function getMessage(): ?string
    {
        if (null !== $this->message) {
            return $this->message;
        }
        return 'Khalil1608_lib.phone_number.invalid';
    }

    public function getTargets(): string|array
    {
        return [self::CLASS_CONSTRAINT, self::PROPERTY_CONSTRAINT];
    }

    public function getErrorPath(): string
    {
        return $this->errorPath;
    }
}
