<?php

namespace Tuto\Routing;

class PathParameter
{
    /** @var string default regex accept all content without "/" */
    public const string DEFAULT_PATH_REGEX = '[^\/]+';

    /** @var array<string, string> all aliases available */
    public const array ALIASES = [
        'int' => '[0-9]+',
        'float' => '[0-9]+(?:\.[0-9]+)?',
        'ulid' => '[0-9A-HJKMNP-TV-Z]{26}|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
        'slug' => '[a-zA-Z0-9_\-]+',
    ];

}