<?php

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class Mod97 extends Constraint
{
    public string $message = 'Broj računa nije validan. Primeri validnih brojeva računa su: 265104031000361092, 150000002501288698.';
}
