<?php

declare(strict_types=1);

namespace MiGears\Utils;

/**
 * Array utility with commonly used static methods.
 *
 * Provides functional-style array operations that are missing from
 * or more convenient than PHP's built-in array functions.
 */
final class Arr
{
    public const VERSION = '2.0.0';

    /**
     * Get an item from an array using "dot" notation.
     *
     *   Arr::get($arr, 'user.name')  // $arr['user']['name']
     *   Arr::get($arr, 'a.b.c', 'default')
     */
    public static function get(array $array, string|int|null $key, mixed $default = null): mixed
    {
        if ($key === null) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (!str_contains((string) $key, '.')) {
            return $default;
        }

        foreach (explode('.', (string) $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }

        return $array;
    }

    /**
     * Set an item in an array using "dot" notation.
     */
    public static function set(array &$array, string|int|null $key, mixed $value): void
    {
        if ($key === null) {
            $array = $value;
            return;
        }

        $keys = explode('.', (string) $key);

        foreach ($keys as $i => $segment) {
            if ($i === count($keys) - 1) {
                $array[$segment] = $value;
            } else {
                if (!isset($array[$segment]) || !is_array($array[$segment])) {
                    $array[$segment] = [];
                }
                $array = &$array[$segment];
            }
        }
    }

    /**
     * Check if an item exists in an array using "dot" notation.
     */
    public static function has(array $array, string|int|null $key): bool
    {
        if ($key === null) {
            return false;
        }

        if (array_key_exists($key, $array)) {
            return true;
        }

        if (!str_contains((string) $key, '.')) {
            return false;
        }

        foreach (explode('.', (string) $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    /**
     * Pluck a list of column values from an array of arrays or objects.
     *
     *   Arr::pluck($users, 'name')          // ['Alice', 'Bob']
     *   Arr::pluck($users, 'email', 'id')   // [1 => 'alice@...', 2 => 'bob@...']
     *
     * @param array<int|string, mixed> $array
     * @return array<int|string, mixed>
     */
    public static function pluck(array $array, string $value, ?string $key = null): array
    {
        $result = [];

        foreach ($array as $item) {
            $itemValue = self::extractValue($item, $value);

            if ($key !== null) {
                $itemKey = self::extractValue($item, $key);
                $result[$itemKey] = $itemValue;
            } else {
                $result[] = $itemValue;
            }
        }

        return $result;
    }

    /**
     * Return the first element in an array passing a truth test.
     *
     * If no callback is given, returns the first element.
     *
     * @param array<int|string, mixed> $array
     */
    public static function first(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $array === [] ? $default : reset($array);
        }

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return $default;
    }

    /**
     * Return the last element in an array passing a truth test.
     *
     * If no callback is given, returns the last element.
     *
     * @param array<int|string, mixed> $array
     */
    public static function last(array $array, ?callable $callback = null, mixed $default = null): mixed
    {
        if ($callback === null) {
            return $array === [] ? $default : end($array);
        }

        $result = $default;
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                $result = $value;
            }
        }

        return $result;
    }

    /**
     * Filter array items based on a callback.
     *
     * Unlike array_filter, preserves numeric keys by default.
     * Pass $preserveKeys = true to keep original keys.
     *
     * @param array<int|string, mixed> $array
     * @return array<int|string, mixed>
     */
    public static function where(array $array, callable $callback, bool $preserveKeys = false): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                if ($preserveKeys) {
                    $result[$key] = $value;
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Get a subset of the items from the given array (whitelist).
     *
     * @param array<int|string, mixed> $array
     * @param list<string|int> $keys
     * @return array<int|string, mixed>
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Get all items except for a specified array of keys (blacklist).
     *
     * @param array<int|string, mixed> $array
     * @param list<string|int> $keys
     * @return array<int|string, mixed>
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Flatten a multi-dimensional array into a single level.
     *
     * @param array<int|string, mixed> $array
     * @param int $depth Maximum depth to flatten (0 = all the way down)
     * @return list<mixed>
     */
    public static function flatten(array $array, int $depth = 0): array
    {
        $result = [];

        foreach ($array as $item) {
            if (is_array($item)) {
                if ($depth === 1) {
                    foreach ($item as $v) {
                        $result[] = $v;
                    }
                } else {
                    $subDepth = $depth > 0 ? $depth - 1 : 0;
                    foreach (self::flatten($item, $subDepth) as $v) {
                        $result[] = $v;
                    }
                }
            } else {
                $result[] = $item;
            }
        }

        return $result;
    }

    /**
     * Collapse an array of arrays into a single array.
     *
     * @param array<int|string, array> $array
     * @return list<mixed>
     */
    public static function collapse(array $array): array
    {
        return self::flatten($array, 1);
    }

    /**
     * Determine whether all elements pass the truth test.
     *
     * Empty array returns true.
     *
     * @param array<int|string, mixed> $array
     */
    public static function every(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if (!$callback($value, $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether at least one element passes the truth test.
     *
     * Empty array returns false.
     *
     * @param array<int|string, mixed> $array
     */
    public static function some(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get unique values from an array (preserves order).
     *
     * @param array<int|string, mixed> $array
     * @return list<mixed>
     */
    public static function unique(array $array): array
    {
        $result = [];
        foreach ($array as $value) {
            if (!in_array($value, $result, true)) {
                $result[] = $value;
            }
        }
        return $result;
    }

    /**
     * Extract a value from an array or object.
     */
    private static function extractValue(mixed $item, string $key): mixed
    {
        return match (true) {
            is_array($item) => $item[$key] ?? null,
            is_object($item) => $item->$key ?? null,
            default => null,
        };
    }
}
