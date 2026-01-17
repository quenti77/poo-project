<?php

namespace Tuto\Template;

use DateTime;
use DateTimeInterface;

class CoreFilters
{
    public static function register(FilterRegistry $registry): void
    {
        // Strings
        $registry->register('upper', static fn($v) => mb_strtoupper((string)$v));
        $registry->register('lower', static fn($v) => mb_strtolower((string)$v));
        $registry->register('ucfirst', static fn($v) => ucfirst((string)$v));
        $registry->register('ucwords', static fn($v) => ucwords((string)$v));
        $registry->register('trim', static fn($v) => trim((string)$v));
        $registry->register('ltrim', static fn($v) => ltrim((string)$v));
        $registry->register('rtrim', static fn($v) => rtrim((string)$v));
        $registry->register('length', static fn($v) => is_array($v) ? count($v) : mb_strlen((string)$v));

        $registry->register('truncate', static function ($v, int $length = 30, string $suffix = '...') {
            $str = (string)$v;
            if (mb_strlen($str) <= $length) {
                return $str;
            }
            return mb_substr($str, 0, $length) . $suffix;
        });

        $registry->register('replace', static fn($v, string $search, string $replace) => str_replace($search, $replace, (string)$v)
        );

        $registry->register('split', static fn($v, string $delimiter = '') => $delimiter === '' ? mb_str_split((string)$v) : explode($delimiter, (string)$v)
        );

        $registry->register('nl2br', static fn($v) => nl2br(htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'))
        );

        $registry->register('striptags', static fn($v) => strip_tags((string)$v));

        $registry->register('slug', static function ($v) {
            $str = (string)$v;
            $str = transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $str);
            $str = preg_replace('/[^a-z0-9]+/', '-', $str);
            return trim($str, '-');
        });

        // Numbers
        $registry->register('number_format', static fn($v, int $decimals = 0, string $dec = ',', string $thousands = ' ') => number_format((float)$v, $decimals, $dec, $thousands)
        );

        $registry->register('round', static fn($v, int $precision = 0) => round((float)$v, $precision));
        $registry->register('floor', static fn($v) => floor((float)$v));
        $registry->register('ceil', static fn($v) => ceil((float)$v));
        $registry->register('abs', static fn($v) => abs((float)$v));

        // Arrays
        $registry->register('first', static fn($v) => is_array($v) ? reset($v) : $v);
        $registry->register('last', static fn($v) => is_array($v) ? end($v) : $v);
        $registry->register('join', static fn($v, string $glue = ', ') => is_array($v) ? implode($glue, $v) : $v);
        $registry->register('keys', static fn($v) => is_array($v) ? array_keys($v) : []);
        $registry->register('values', static fn($v) => is_array($v) ? array_values($v) : []);
        $registry->register('reverse', static fn($v) => is_array($v) ? array_reverse($v) : strrev((string)$v));
        $registry->register('sort', static function ($v) {
            if (!is_array($v)) {
                return $v;
            }
            sort($v);
            return $v;
        });
        $registry->register('rsort', static function ($v) {
            if (!is_array($v)) {
                return $v;
            }
            rsort($v);
            return $v;
        });
        $registry->register('unique', static fn($v) => is_array($v) ? array_unique($v) : $v);
        $registry->register('merge', static fn($v, array $arr) => is_array($v) ? array_merge($v, $arr) : $v);
        $registry->register('slice', static fn($v, int $start, ?int $length = null) => is_array($v) ? array_slice($v, $start, $length) : mb_substr((string)$v, $start, $length)
        );
        $registry->register('column', static fn($v, string $key) => is_array($v) ? array_column($v, $key) : $v);
        $registry->register('filter', static fn($v) => is_array($v) ? array_filter($v) : $v);
        $registry->register('map', static fn($v, string $key) => is_array($v) ? array_map(static fn($item) => $item[$key] ?? null, $v) : $v
        );

        // Dates
        $registry->register('date', static fn($v, string $format = 'd/m/Y') => $v instanceof DateTimeInterface
            ? $v->format($format)
            : date($format, is_numeric($v) ? (int)$v : strtotime((string)$v))
        );

        $registry->register('date_modify', static function ($v, string $modify) {
            if ($v instanceof DateTimeInterface) {
                return (clone $v)->modify($modify);
            }
            return new DateTime(is_numeric($v) ? "@$v" : (string)$v)->modify($modify);
        });

        // Security / Escaping
        $registry->register('escape', static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'));
        $registry->register('e', static fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'));
        $registry->register('raw', static fn($v) => $v);

        $registry->register('url_encode', static fn($v) => urlencode((string)$v));
        $registry->register('json', static fn($v, int $flags = 0) => json_encode($v, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | $flags)
        );

        // Default / Fallback
        $registry->register('default', static fn($v, $default = '') => $v ?? $default);
        $registry->register('empty_default', static fn($v, $default = '') => empty($v) ? $default : $v);

        // Type conversion
        $registry->register('int', static fn($v) => (int)$v);
        $registry->register('float', static fn($v) => (float)$v);
        $registry->register('string', static fn($v) => (string)$v);
        $registry->register('bool', static fn($v) => (bool)$v);
        $registry->register('array', static fn($v) => (array)$v);

        // Debug
        $registry->register('dump', static function ($v) {
            ob_start();
            dump($v);
            return ob_get_clean();
        });
        $registry->register('type', static fn($v) => get_debug_type($v));

        // Translation
        $registry->register('trans', static fn($v, int|float $count = 1, array $context = []) => trans((string) $v, $count, $context));
    }
}
