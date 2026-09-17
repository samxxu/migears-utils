<?php

declare(strict_types=1);

namespace MiGears\Utils;

use InvalidArgumentException;

/**
 * URL utility for safe building, parameter manipulation, and path handling.
 *
 * Immutable — all modifications return a new instance.
 */
final class URL
{
    private readonly array $parts;

    public function __construct(
        string $scheme = 'https',
        string $host = '',
        ?int $port = null,
        string $path = '/',
        array $query = [],
        ?string $fragment = null,
    ) {
        if ($host === '') {
            throw new InvalidArgumentException('URL host cannot be empty');
        }

        $this->parts = [
            'scheme' => strtolower($scheme),
            'host' => strtolower($host),
            'port' => $port,
            'path' => $path === '' || $path[0] !== '/' ? '/' . ltrim($path, '/') : $path,
            'query' => $query,
            'fragment' => $fragment,
        ];
    }

    /** Parse a URL string. Adds "https://" if no scheme is present. */
    public static function parse(string $url): self
    {
        if (!preg_match('/^[\w]+:\/\//i', $url)) {
            $url = 'https://' . $url;
        }

        $parts = parse_url($url);
        if ($parts === false) {
            throw new InvalidArgumentException("Malformed URL: {$url}");
        }

        $query = [];
        if (isset($parts['query'])) {
            parse_str($parts['query'], $query);
        }

        return new self(
            scheme: $parts['scheme'] ?? 'https',
            host: $parts['host'] ?? '',
            port: isset($parts['port']) ? (int) $parts['port'] : null,
            path: $parts['path'] ?? '/',
            query: $query,
            fragment: $parts['fragment'] ?? null,
        );
    }

    public function scheme(): string { return $this->parts['scheme']; }
    public function host(): string { return $this->parts['host']; }
    public function port(): ?int { return $this->parts['port']; }
    public function path(): string { return $this->parts['path']; }
    public function query(): array { return $this->parts['query']; }
    public function fragment(): ?string { return $this->parts['fragment']; }

    /** Get a single query parameter, or $default if not set. */
    public function queryParam(string $key, mixed $default = null): mixed
    {
        return $this->parts['query'][$key] ?? $default;
    }

    /** Return a new URL with the given scheme. */
    public function withScheme(string $scheme): self
    {
        return $this->with(['scheme' => strtolower($scheme)]);
    }

    /** Return a new URL with the given host. */
    public function withHost(string $host): self
    {
        return $this->with(['host' => strtolower($host)]);
    }

    /** Return a new URL with the given port (null for default). */
    public function withPort(?int $port): self
    {
        return $this->with(['port' => $port]);
    }

    /** Return a new URL with the given path. */
    public function withPath(string $path): self
    {
        $path = $path === '' || $path[0] !== '/' ? '/' . ltrim($path, '/') : $path;
        return $this->with(['path' => $path]);
    }

    /** Return a new URL with query parameters merged in. Null values remove the key. */
    public function withQuery(array $params): self
    {
        $query = $this->parts['query'];
        foreach ($params as $key => $value) {
            if ($value === null) {
                unset($query[$key]);
            } else {
                $query[$key] = $value;
            }
        }
        return $this->with(['query' => $query]);
    }

    /** Return a new URL with a single query parameter set. */
    public function withQueryParam(string $key, mixed $value): self
    {
        return $this->withQuery([$key => $value]);
    }

    /** Return a new URL without the given query parameter. */
    public function withoutQueryParam(string $key): self
    {
        return $this->withQuery([$key => null]);
    }

    /** Return a new URL with the given fragment (null to remove). */
    public function withFragment(?string $fragment): self
    {
        return $this->with(['fragment' => $fragment]);
    }

    /** Append a path segment and return a new URL. */
    public function appendPath(string $segment): self
    {
        $path = rtrim($this->parts['path'], '/') . '/' . ltrim($segment, '/');
        return $this->with(['path' => $path]);
    }

    /** Whether this URL uses HTTPS. */
    public function isHttps(): bool
    {
        return $this->parts['scheme'] === 'https';
    }

    /** Build and return the full URL string. */
    public function toString(): string
    {
        $p = $this->parts;
        $url = "{$p['scheme']}://{$p['host']}";

        if ($p['port'] !== null && !$this->isDefaultPort()) {
            $url .= ':' . $p['port'];
        }

        $url .= $p['path'];

        if ($p['query'] !== []) {
            $url .= '?' . http_build_query($p['query']);
        }

        if ($p['fragment'] !== null) {
            $url .= '#' . $p['fragment'];
        }

        return $url;
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /** Create a new URL instance with modified parts. */
    private function with(array $changes): self
    {
        $p = array_merge($this->parts, $changes);
        return new self(
            scheme: $p['scheme'],
            host: $p['host'],
            port: $p['port'],
            path: $p['path'],
            query: $p['query'],
            fragment: $p['fragment'],
        );
    }

    private function isDefaultPort(): bool
    {
        return match ($this->parts['scheme']) {
            'http' => $this->parts['port'] === 80,
            'https' => $this->parts['port'] === 443,
            default => false,
        };
    }
}
