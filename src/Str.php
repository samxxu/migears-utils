<?php

declare(strict_types=1);

namespace MiGears\Utils;

/**
 * String utility with commonly used static methods.
 *
 * All methods are multibyte-safe where applicable (uses mb_* functions).
 */
final class Str
{
    public const VERSION = '2.0.0';

    /** Check if a string starts with a given substring. */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /** Check if a string ends with a given substring. */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /** Check if a string contains a given substring. */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /** Get the length of a string (multibyte-safe). */
    public static function length(string $string, ?string $encoding = null): int
    {
        return mb_strlen($string, $encoding ?? 'UTF-8');
    }

    /** Convert a string to a URL-friendly slug. */
    public static function slug(string $string, string $separator = '-'): string
    {
        $string = self::ascii($string);
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9]+/', $separator, $string);
        $string = trim($string, $separator);
        $string = preg_replace('/' . preg_quote($separator, '/') . '{2,}/', $separator, $string);
        return $string;
    }

    /** Truncate a string to a given length with ellipsis (multibyte-safe). */
    public static function truncate(string $string, int $length = 100, string $ellipsis = '...'): string
    {
        if (self::length($string) <= $length) {
            return $string;
        }
        $trimLength = max(0, $length - self::length($ellipsis));
        return mb_substr($string, 0, $trimLength, 'UTF-8') . $ellipsis;
    }

    /** Limit the number of words in a string. */
    public static function words(string $string, int $words = 100, string $end = '...'): string
    {
        preg_match('/^\s*+(?:\S++\s*+){1,' . $words . '}/u', $string, $matches);
        if (!isset($matches[0]) || self::length($string) === self::length($matches[0])) {
            return $string;
        }
        return rtrim($matches[0]) . $end;
    }

    /** Convert a string to camelCase. */
    public static function camel(string $string): string
    {
        return lcfirst(self::studly($string));
    }

    /** Convert a string to StudlyCase (PascalCase). */
    public static function studly(string $string): string
    {
        return implode('', array_map('ucfirst', preg_split('/[\s_-]+/', $string)));
    }

    /** Convert a string to snake_case. */
    public static function snake(string $string, string $delimiter = '_'): string
    {
        $snake = preg_replace('/([a-z])([A-Z])/', '$1' . $delimiter . '$2', $string);
        $snake = preg_replace('/[\s-]+/', $delimiter, $snake);
        return strtolower($snake);
    }

    /** Convert a string to kebab-case. */
    public static function kebab(string $string): string
    {
        return self::snake($string, '-');
    }

    /** Capitalize the first letter of a string (multibyte-safe). */
    public static function ucfirst(string $string): string
    {
        return mb_strtoupper(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /** Make a string's first character lowercase (multibyte-safe). */
    public static function lcfirst(string $string): string
    {
        return mb_strtolower(mb_substr($string, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($string, 1, null, 'UTF-8');
    }

    /** Convert a string to its ASCII equivalent. */
    public static function ascii(string $string): string
    {
        if (function_exists('transliterator_transliterate')) {
            return transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $string) ?? $string;
        }
        // Fallback: common character map
        return strtr($string, [
            'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae',
            'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e',
            'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'ñ' => 'n',
            'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o',
            'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ý' => 'y', 'ÿ' => 'y',
            'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE',
            'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N',
            'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
            'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y', 'Ÿ' => 'Y',
        ]);
    }

    /** Generate a URL-friendly random string (cryptographically secure). */
    public static function random(int $length = 16): string
    {
        if ($length < 1) {
            throw new \InvalidArgumentException('Length must be at least 1');
        }
        $bytes = random_bytes((int) ceil($length * 0.75));
        return substr(rtrim(strtr(base64_encode($bytes), '+/', '-_'), '='), 0, $length);
    }

    /** Reverse a string (multibyte-safe). */
    public static function reverse(string $string): string
    {
        $length = mb_strlen($string, 'UTF-8');
        $result = '';
        for ($i = $length - 1; $i >= 0; $i--) {
            $result .= mb_substr($string, $i, 1, 'UTF-8');
        }
        return $result;
    }
}
