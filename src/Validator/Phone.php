<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Phone extends Constraint
{
    public string $message = 'Broj telefona nije validan. Primeri validnih brojeva: 0651234567, 065123456, 0112345678';
}
