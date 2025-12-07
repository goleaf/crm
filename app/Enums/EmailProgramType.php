<?php

declare(strict_types=1);

namespace App\Enums;

enum EmailProgramType: string
{
    case DRIP = 'drip';
    case NURTURE = 'nurture';
    case AB_TEST = 'ab_test';
    case ONE_TIME = 'one_time';
}
