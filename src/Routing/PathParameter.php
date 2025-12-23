<?php

namespace Tuto\Routing;

use Tuto\Collections\Collection;

class PathParameter
{
    /** @var string default regex accept all content without "/" */
    public const string DEFAULT_PATH_REGEX = '[^\/]+';

    /** @var array<string, string> all aliases available */
    private const array DEFAULT_ALIASES = [
        'int' => '[0-9]+',
        'float' => '[0-9]+(?:\.[0-9]+)?',
        'ulid' => '[0-9A-HJKMNP-TV-Z]{26}|[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
        'slug' => '[a-zA-Z0-9_\-]+',
    ];

    /** @var Collection<string, string> $aliases */
    private Collection $aliases;

    /**
     * @param array<string, string> $init
     */
    public function __construct(array $init = [])
    {
        $this->aliases = collect(self::DEFAULT_ALIASES)->merge($init);
    }

    /**
     * @param string $alias
     * @return string
     */
    public function get(string $alias): string
    {
        return $this->aliases[$alias] ?? self::DEFAULT_PATH_REGEX;
    }
}