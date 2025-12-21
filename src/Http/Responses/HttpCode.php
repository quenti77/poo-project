<?php

namespace Tuto\Http\Responses;

enum HttpCode: int
{
    // 2XX
    case OK = 200;
    case CREATED = 201;
    case NO_CONTENT = 204;

    // 3XX
    case MOVED_PERMANENTLY = 301;
    case FOUND = 302;
    case NOT_MODIFIED = 304;
    case TEMPORARY_REDIRECT = 307;
    case PERMANENT_REDIRECT = 308;

    // 4XX
    case BAD_REQUEST = 400;
    case UNAUTHORIZED = 401;
    case FORBIDDEN = 403;
    case NOT_FOUND = 404;
    case METHOD_NOT_ALLOWED = 405;
    case IM_A_TEAPOT = 418;
    case TOO_MANY_REQUESTS = 429;

    // 5XX
    case INTERNAL_SERVER_ERROR = 500;
    case SERVICE_UNAVAILABLE = 503;

    public function label(): string
    {
        return match ($this) {
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::NO_CONTENT => 'No Content',
            self::MOVED_PERMANENTLY => 'Moved Permanently',
            self::FOUND => 'Found',
            self::NOT_MODIFIED => 'Not Modified',
            self::TEMPORARY_REDIRECT => 'Temporary Redirect',
            self::PERMANENT_REDIRECT => 'Permanent Redirect',
            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::IM_A_TEAPOT => 'I\'m a teapot',
            self::TOO_MANY_REQUESTS => 'Too Many Requests',
            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',
        };
    }

    public function hasNoContent(): bool
    {
        return match ($this) {
            self::NO_CONTENT,
            self::MOVED_PERMANENTLY,
            self::FOUND,
            self::NOT_MODIFIED,
            self::TEMPORARY_REDIRECT,
            self::PERMANENT_REDIRECT => true,
            default => false,
        };
    }
}