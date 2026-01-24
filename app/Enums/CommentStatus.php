<?php

namespace App\Enums;

use Tuto\Utils\EasyEnum;

enum CommentStatus: string
{
    use EasyEnum;

    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
}
