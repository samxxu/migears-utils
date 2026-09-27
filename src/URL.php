<?php

declare(strict_types=1);

namespace MiGears\Utils;

use InvalidArgumentException;

/**
 * URL utility for safe building, parameter manipulation, and path handling.
 *
 * Absolute URLs only — a scheme and a host are always required, and neither
 * is ever guessed. Use parse() for strings that already carry a scheme, or
 * http() / https() to force a scheme onto a string.
 *
 * Everything it accepts, it accepts on the way in: scheme, host, and port are
 * validated, the path and fragment are percent-encoded, and a null query value
 * means the parameter is absent. The constructor is the only place that
 * checks, so every with*() method inherits the same guarantees.
 *
 * Immutable — all modifications return a new instance.
 */
final class URL
{
    /**
     * @var array{scheme: string, host: string, port: int|null, path: string, query: array<string, mixed>, fragment: string|null, user: string|null, pass: string|null}
     */
    private readonly array $parts;

    /**
     * The host accepts an RFC 3986 reg-name (a hostname, an IPv4 address, or
     * the permissive unreserved/sub-delims set) or an IP literal in brackets
     * such as [::1]. What matters is what that excludes: a space, CR, or LF
     * would break out of the authority and reach a client as a second header
     * or request line. The port must be a real destination port.
     *
     * $path and $fragment are percent-encoded on the way in, leaving existing
     * escape sequences intact. An empty user or password counts as absent.
     *
     * @param array<string, mixed> $query Query parameters; null values are dropped
     * @param string|null $user Userinfo user name, without percent-encoding
     * @param string|null $pass Userinfo password, without percent-encoding
     * @throws InvalidArgumentException When the scheme, host, port, or userinfo is not usable
     */
    public function __construct(
        string $scheme,
        string $host,
        ?int $port = null,
        string $path = '/',
        array $query = [],
        ?string $fragment = null,
        ?string $user = null,
        ?string $pass = null,
    ) {
        if ($scheme === '') {
            throw new InvalidArgumentException('URL scheme cannot be empty');
        }

        $scheme = strtolower($scheme);

        if (!preg_match('/^[a-z][a-z0-9+.\-]*$/', $scheme)) {
            throw new InvalidArgumentException("Invalid URL scheme: {$scheme}");
        }

        if ($host === '') {
            throw new InvalidArgumentException('URL host cannot be empty');
        }

        self::assertValidHost($host);

        if ($port !== null && ($port < 1 || $port > 65535)) {
            throw new InvalidArgumentException("URL port must be between 1 and 65535, got {$port}");
        }

        $user = $user === '' ? null : $user;
        $pass = $pass === '' ? null : $pass;

        if ($pass !== null && $user === null) {
            throw new InvalidArgumentException('URL password requires a user');
        }

        $path = self::encodePath($path);

        $this->parts = [
            'scheme' => $scheme,
            'host' => strtolower($host),
            'port' => $port,
            'path' => $path === '' || $path[0] !== '/' ? '/' . ltrim($path, '/') : $path,
            'query' => self::withoutNulls($query),
            'fragment' => $fragment === null ? null : self::encodeFragment($fragment),
            'user' => $user,
            'pass' => $pass,
        ];
    }

    /**
     * Parse a URL that already carries its own scheme.
     *
     * @throws InvalidArgumentException when the scheme is missing or the string is malformed
     */
    public static function parse(string $url): self
    {
        return self::build($url, null);
    }

    /** Parse a URL and force the http scheme, whatever the string says. */
    public static function http(string $url): self
    {
        return self::build($url, 'http');
    }

    /** Parse a URL and force the https scheme, whatever the string says. */
    public static function https(string $url): self
    {
        return self::build($url, 'https');
    }

    public function scheme(): string { return $this->parts['scheme']; }
    public function host(): string { return $this->parts['host']; }
    public function port(): ?int { return $this->parts['port']; }
    public function path(): string { return $this->parts['path']; }
    /** @return array<string, mixed> */
    public function query(): array { return $this->parts['query']; }
    public function fragment(): ?string { return $this->parts['fragment']; }
    public function user(): ?string { return $this->parts['user']; }
    public function pass(): ?string { return $this->parts['pass']; }

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

    /**
     * Return a new URL with query parameters merged in.
     *
     * Null values remove the key — the same meaning null carries in the
     * constructor, applied through the one place that normalises.
     *
     * @param array<string, mixed> $params
     */
    public function withQuery(array $params): self
    {
        return $this->with(['query' => array_merge($this->parts['query'], $params)]);
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

    /**
     * Return a new URL with the given userinfo pair.
     *
     * Both parts are set together: a null $user removes the userinfo entirely,
     * and omitting $pass clears any existing password.
     */
    public function withUser(?string $user, ?string $pass = null): self
    {
        return $this->with(['user' => $user, 'pass' => $pass]);
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
        $url = "{$p['scheme']}://";

        if ($p['user'] !== null) {
            $url .= rawurlencode($p['user']);

            if ($p['pass'] !== null) {
                $url .= ':' . rawurlencode($p['pass']);
            }

            $url .= '@';
        }

        $url .= $p['host'];

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

    /**
     * Shared parsing path.
     *
     * When $scheme is given it is forced onto the result — both filling a
     * missing scheme and overriding a different one. When it is null the URL
     * must carry its own scheme, and a missing one is rejected.
     */
    private static function build(string $url, ?string $scheme): self
    {
        if (!preg_match('/^[a-z][a-z0-9+.\-]*:\/\//i', $url)) {
            if ($scheme === null) {
                throw new InvalidArgumentException("URL has no scheme: {$url}");
            }
            $url = $scheme . '://' . ltrim($url, '/');
        }

        $parts = parse_url($url);

        if ($parts === false || !isset($parts['host']) || $parts['host'] === '') {
            throw new InvalidArgumentException("Malformed URL: {$url}");
        }

        return new self(
            scheme: $scheme ?? $parts['scheme'],
            host: $parts['host'],
            port: isset($parts['port']) ? (int) $parts['port'] : null,
            path: $parts['path'] ?? '/',
            query: self::splitQuery($parts['query'] ?? ''),
            fragment: $parts['fragment'] ?? null,
            user: isset($parts['user']) ? rawurldecode($parts['user']) : null,
            pass: isset($parts['pass']) ? rawurldecode($parts['pass']) : null,
        );
    }

    /**
     * Percent-encode characters that cannot appear literally in a path, while
     * leaving existing escape sequences alone so an already-encoded path
     * survives a round trip unchanged.
     */
    private static function encodePath(string $path): string
    {
        return (string) preg_replace_callback(
            '/[^A-Za-z0-9\-._~!$&\'()*+,;=:@\/%]|%(?![A-Fa-f0-9]{2})/',
            static fn (array $match): string => rawurlencode($match[0]),
            $path,
        );
    }

    /** Percent-encode a fragment, allowing the same characters as a path plus "?". */
    private static function encodeFragment(string $fragment): string
    {
        return (string) preg_replace_callback(
            '/[^A-Za-z0-9\-._~!$&\'()*+,;=:@\/?%]|%(?![A-Fa-f0-9]{2})/',
            static fn (array $match): string => rawurlencode($match[0]),
            $fragment,
        );
    }

    /**
     * Split a raw query string into key/value pairs.
     *
     * Unlike parse_str(), keys keep their original characters — dots and
     * spaces are not rewritten to underscores. Bracket notation is not
     * expanded, so every key is flat and every value is a scalar.
     *
     * @return array<string, string>
     */
    private static function splitQuery(string $query): array
    {
        $result = [];

        foreach ($query === '' ? [] : explode('&', $query) as $pair) {
            if ($pair === '') {
                continue;
            }

            [$key, $value] = array_pad(explode('=', $pair, 2), 2, '');
            $result[urldecode($key)] = urldecode($value);
        }

        return $result;
    }

    /**
     * Create a new URL instance with modified parts.
     *
     * @param array<string, mixed> $changes
     */
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
            user: $p['user'],
            pass: $p['pass'],
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

    /**
     * Reject a host that could break out of the authority.
     *
     * A reg-name is any run of unreserved or sub-delim characters with valid
     * percent escapes; an IP literal is anything inside brackets. Both exclude
     * the characters that would let a host smuggle a header, a space, or a
     * path separator into a request line.
     */
    private static function assertValidHost(string $host): void
    {
        $regName = '/^(?:[A-Za-z0-9._~!$&\'()*+,;=-]|%[A-Fa-f0-9]{2})*$/';
        $ipLiteral = '/^\[[0-9A-Za-z:.%_-]+\]$/';

        if (preg_match($regName, $host) !== 1 && preg_match($ipLiteral, $host) !== 1) {
            throw new InvalidArgumentException('Invalid URL host: ' . (json_encode($host) ?: 'unrepresentable'));
        }
    }

    /**
     * Null means "no such parameter" everywhere in this class: the constructor
     * drops null values and withQuery() deletes them. Keeping one here would
     * let query() report a key that toString() never emits, since
     * http_build_query() skips nulls too.
     *
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private static function withoutNulls(array $query): array
    {
        return array_filter($query, static fn (mixed $value): bool => $value !== null);
    }
}
