<?php

namespace App\Validator;

use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PhoneValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof Phone) {
            throw new UnexpectedTypeException($constraint, Phone::class);
        }

        if (null === $value) {
            return;
        }

        if (!is_string($value) || '' === $value) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();

            return;
        }

        if (!$this->validateSerbianPhoneNumber($value)) {
            $this->context->buildViolation($constraint->message)
                ->addViolation();
        }
    }

    private function validateSerbianPhoneNumber(string $phoneNumber): bool
    {
        $phoneUtil = PhoneNumberUtil::getInstance();

        try {
            // Pre-validate: must be 9 or 10 digits starting with 0
            if (!preg_match('/^0\d{8,9}$/', $phoneNumber)) {
                return false;
            }

            // Convert to international format for libphonenumber
            $phoneNumber = '+381'.substr($phoneNumber, 1);

            $numberProto = $phoneUtil->parse($phoneNumber, 'RS');

            return $phoneUtil->isValidNumberForRegion($numberProto, 'RS');
        } catch (NumberParseException) {
            return false;
        }
    }
}
