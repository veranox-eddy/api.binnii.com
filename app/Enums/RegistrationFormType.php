<?php

namespace App\Enums;

use App\Enums\Concerns\HasValues;

enum RegistrationFormType: string
{
    use HasValues;

    case Applicant = 'applicant';
    case Package = 'package';
}
