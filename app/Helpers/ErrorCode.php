<?php

declare(strict_types=1);

namespace App\Helpers;

enum ErrorCode: string {
    case NO_TOKEN = 'no_token';
    case NO_WALLET = 'no_wallet';
    case USER_NOT_FOUND_BY_TOKEN = 'user_not_found_by_token';
    case USERNAME_NOT_USERS = 'username_not_users';
    case USER_NOT_LOGGED_IN = 'user_not_logged_in';
    case COULD_NOT_PARSE_TEMPLATE = 'could_not_parse_template';
}
