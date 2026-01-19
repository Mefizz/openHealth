<?php

declare(strict_types=1);

namespace App\Enums\Person;

use App\Traits\EnumUtils;

/**
 * List of constants for navigation by modals for interaction with auth methods.
 */
enum AuthStep: int
{
    use EnumUtils;

    case INITIAL = 0;
    case CHANGE_PHONE_INITIAL = 1;
    case VERIFY_PHONE = 2;
    case NO_PHONE_ACCESS = 3;
    case COMPLETE_VERIFICATION = 4;
    case CHANGE_FROM_OFFLINE = 5;
    case CHANGE_PHONE = 6;
    case CHANGE_ALIAS = 7;
    case UPDATE_ALIAS = 8;
    case ADD_NEW_BY_SMS = 9;
    case APPROVE_ADDING_BY_SMS = 10;
    case ADD_NEW_BY_DOCUMENT = 11;
}
