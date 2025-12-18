<?php

namespace Tuto\Http\Requests;

use Tuto\Utils\EasyEnum;

enum HttpMethod: string
{
    use EasyEnum;

    case GET = 'GET';
    case POST = 'POST';
    case PUT = 'PUT';
    case PATCH = 'PATCH';
    case DELETE = 'DELETE';
    case HEAD = 'HEAD';
    case OPTIONS = 'OPTIONS';
}
