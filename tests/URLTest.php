<?php

declare(strict_types=1);

namespace MiGears\Utils\Tests;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use MiGears\Utils\URL;

class URLTest extends TestCase
{
    // --- Construction ---

    public function testConstructBasic(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');

        $this->assertSame('https', $url->scheme());
        $this->assertSame('example.com', $url->host());
        $this->assertSame('/', $url->path());
        $this->assertNull($url->port());
    }

    public function testConstructWithAllParts(): void
    {
        $url = new URL(
            scheme: 'https',
            host: 'example.com',
            port: 8080,
            path: '/api/users',
            query: ['page' => '1', 'limit' => '10'],
            fragment: 'section',
        );

        $this->assertSame('https', $url->scheme());
        $this->assertSame('example.com', $url->host());
        $this->assertSame(8080, $url->port());
        $this->assertSame('/api/users', $url->path());
        $this->assertSame(['page' => '1', 'limit' => '10'], $url->query());
        $this->assertSame('section', $url->fragment());
    }

    public function testConstructNormalizesPath(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', path: 'no-slash');

        $this->assertSame('/no-slash', $url->path());
    }

    public function testConstructEmptyHostThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new URL(scheme: 'https', host: '');
    }

    public function testConstructEmptySchemeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new URL(scheme: '', host: 'example.com');
    }

    // --- parse ---

    public function testParseFullUrl(): void
    {
        $url = URL::parse('https://example.com:8080/path/to/page?foo=bar&baz=qux#section');

        $this->assertSame('https', $url->scheme());
        $this->assertSame('example.com', $url->host());
        $this->assertSame(8080, $url->port());
        $this->assertSame('/path/to/page', $url->path());
        $this->assertSame(['foo' => 'bar', 'baz' => 'qux'], $url->query());
        $this->assertSame('section', $url->fragment());
    }

    public function testParseWithoutSchemeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        URL::parse('example.com/path');
    }

    public function testParseRejectsSchemeStartingWithDigit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        URL::parse('1a://example.com');
    }

    public function testParseMalformedUrlThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        URL::parse('http:///bad');
    }

    // --- http / https factories ---

    public function testHttpFactoryFillsMissingScheme(): void
    {
        $url = URL::http('example.com/path');

        $this->assertSame('http', $url->scheme());
        $this->assertSame('example.com', $url->host());
        $this->assertSame('/path', $url->path());
    }

    public function testHttpsFactoryFillsMissingScheme(): void
    {
        $url = URL::https('example.com/path');

        $this->assertSame('https', $url->scheme());
        $this->assertSame('example.com', $url->host());
        $this->assertSame('/path', $url->path());
    }

    public function testFactoriesForceScheme(): void
    {
        $this->assertSame('https', URL::https('http://example.com')->scheme());
        $this->assertSame('http', URL::http('https://example.com')->scheme());
        $this->assertSame('https', URL::https('example.com')->scheme());
        $this->assertSame('http', URL::http('example.com')->scheme());
    }

    public function testFactoryThrowsOnMalformedUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        URL::https('http:///bad');
    }

    // --- userinfo ---

    public function testParseExtractsUserInfo(): void
    {
        $url = URL::parse('https://user:secret@example.com/path');

        $this->assertSame('user', $url->user());
        $this->assertSame('secret', $url->pass());
        $this->assertSame('example.com', $url->host());
        $this->assertSame('/path', $url->path());
    }

    public function testParseUserInfoWithoutPassword(): void
    {
        $url = URL::parse('https://user@example.com');

        $this->assertSame('user', $url->user());
        $this->assertNull($url->pass());
    }

    public function testParseWithoutUserInfoLeavesItNull(): void
    {
        $url = URL::parse('https://example.com');

        $this->assertNull($url->user());
        $this->assertNull($url->pass());
    }

    public function testUserInfoIsPercentEncodedAndDecoded(): void
    {
        $url = URL::parse('https://a%40b:p%3Ass@example.com/');

        $this->assertSame('a@b', $url->user());
        $this->assertSame('p:ss', $url->pass());
        $this->assertSame('https://a%40b:p%3Ass@example.com/', $url->toString());
    }

    public function testUserInfoRoundtripKeepsCredentials(): void
    {
        $original = 'https://user:secret@example.com:8080/path?q=1#frag';

        $this->assertSame($original, URL::parse($original)->toString());
    }

    public function testPasswordWithoutUserThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new URL(scheme: 'https', host: 'example.com', pass: 'secret');
    }

    public function testWithUserSetsPair(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');
        $newUrl = $url->withUser('user', 'secret');

        $this->assertNull($url->user());
        $this->assertSame('user', $newUrl->user());
        $this->assertSame('secret', $newUrl->pass());
    }

    public function testWithUserNullRemovesUserInfo(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', user: 'user', pass: 'secret');
        $newUrl = $url->withUser(null);

        $this->assertSame('user', $url->user());
        $this->assertNull($newUrl->user());
        $this->assertNull($newUrl->pass());
    }

    // --- toString / __toString ---

    public function testToStringBasic(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');

        $this->assertSame('https://example.com/', (string) $url);
    }

    public function testToStringWithPathAndQuery(): void
    {
        $url = new URL(
            scheme: 'https',
            host: 'example.com',
            path: '/search',
            query: ['q' => 'hello world', 'page' => '2'],
        );

        $this->assertSame('https://example.com/search?q=hello+world&page=2', $url->toString());
    }

    public function testToStringWithFragment(): void
    {
        $url = new URL(
            scheme: 'https',
            host: 'example.com',
            path: '/doc',
            fragment: 'section-1',
        );

        $this->assertSame('https://example.com/doc#section-1', $url->toString());
    }

    public function testToStringOmitsDefaultHttpsPort(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', port: 443);

        $this->assertSame('https://example.com/', $url->toString());
    }

    public function testToStringOmitsDefaultHttpPort(): void
    {
        $url = new URL(scheme: 'http', host: 'example.com', port: 80);

        $this->assertSame('http://example.com/', $url->toString());
    }

    public function testToStringIncludesNonDefaultPort(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', port: 8443);

        $this->assertSame('https://example.com:8443/', $url->toString());
    }

    // --- Immutability / with* methods ---

    public function testWithSchemeReturnsNewInstance(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');
        $newUrl = $url->withScheme('http');

        $this->assertSame('https', $url->scheme());
        $this->assertSame('http', $newUrl->scheme());
        $this->assertNotSame($url, $newUrl);
    }

    public function testWithHost(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');
        $newUrl = $url->withHost('other.com');

        $this->assertSame('example.com', $url->host());
        $this->assertSame('other.com', $newUrl->host());
    }

    public function testWithPort(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');
        $newUrl = $url->withPort(8080);

        $this->assertNull($url->port());
        $this->assertSame(8080, $newUrl->port());
    }

    public function testWithPath(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', path: '/old');
        $newUrl = $url->withPath('/new');

        $this->assertSame('/old', $url->path());
        $this->assertSame('/new', $newUrl->path());
    }

    public function testWithFragment(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');
        $newUrl = $url->withFragment('top');

        $this->assertNull($url->fragment());
        $this->assertSame('top', $newUrl->fragment());
    }

    public function testWithFragmentNull(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', fragment: 'top');
        $newUrl = $url->withFragment(null);

        $this->assertSame('top', $url->fragment());
        $this->assertNull($newUrl->fragment());
    }

    // --- Query manipulation ---

    public function testQueryParam(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', query: ['page' => '2']);

        $this->assertSame('2', $url->queryParam('page'));
        $this->assertNull($url->queryParam('missing'));
        $this->assertSame('default', $url->queryParam('missing', 'default'));
    }

    public function testWithQueryMergesParams(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', query: ['a' => '1', 'b' => '2']);
        $newUrl = $url->withQuery(['b' => '99', 'c' => '3']);

        $this->assertSame(['a' => '1', 'b' => '2'], $url->query());
        $this->assertSame(['a' => '1', 'b' => '99', 'c' => '3'], $newUrl->query());
    }

    public function testWithQueryNullRemovesKey(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', query: ['a' => '1', 'b' => '2']);
        $newUrl = $url->withQuery(['a' => null]);

        $this->assertSame(['b' => '2'], $newUrl->query());
    }

    public function testWithQueryParam(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com');
        $newUrl = $url->withQueryParam('foo', 'bar');

        $this->assertSame(['foo' => 'bar'], $newUrl->query());
    }

    public function testWithoutQueryParam(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', query: ['a' => '1', 'b' => '2']);
        $newUrl = $url->withoutQueryParam('a');

        $this->assertSame(['b' => '2'], $newUrl->query());
    }

    public function testParseKeepsQueryKeyCharacters(): void
    {
        $url = URL::parse('https://example.com/?a.b=1&c%20d=2&flag');

        $this->assertSame(['a.b' => '1', 'c d' => '2', 'flag' => ''], $url->query());
    }

    public function testParseKeepsFullyEncodedKey(): void
    {
        $url = URL::parse('https://example.com/?a%2Bb=c%2Bd');

        $this->assertSame(['a+b' => 'c+d'], $url->query());
    }

    // --- Path operations ---

    public function testAppendPath(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', path: '/api');
        $newUrl = $url->appendPath('users');

        $this->assertSame('/api/users', $newUrl->path());
    }

    public function testAppendPathWithLeadingSlash(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', path: '/api/');
        $newUrl = $url->appendPath('/users/');

        $this->assertSame('/api/users/', $newUrl->path());
    }

    // --- isHttps ---

    public function testIsHttps(): void
    {
        $url1 = new URL(scheme: 'https', host: 'example.com');
        $url2 = new URL(scheme: 'http', host: 'example.com');

        $this->assertTrue($url1->isHttps());
        $this->assertFalse($url2->isHttps());
    }

    // --- Round-trip parse and toString ---

    public function testParseAndToStringRoundtrip(): void
    {
        $original = 'https://example.com:8080/path/to/page?foo=bar&baz=qux#frag';
        $url = URL::parse($original);

        $this->assertSame($original, $url->toString());
    }

    // --- scheme validation ---

    public function testConstructWithInvalidSchemeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new URL(scheme: '1a', host: 'example.com');
    }

    public function testWithSchemeRejectsInvalidValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new URL(scheme: 'https', host: 'example.com'))->withScheme('1a b');
    }

    // --- path and fragment encoding ---

    public function testPathIsPercentEncoded(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', path: '/a b/c');

        $this->assertSame('/a%20b/c', $url->path());
        $this->assertSame('https://example.com/a%20b/c', $url->toString());
    }

    public function testFragmentIsPercentEncoded(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', fragment: 'a b');

        $this->assertSame('https://example.com/#a%20b', $url->toString());
    }

    public function testExistingEscapesSurviveEncoding(): void
    {
        $original = 'https://example.com/a%20b?q=1#c%20d';

        $this->assertSame($original, URL::parse($original)->toString());
    }

    public function testLonePercentSignIsEncoded(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', path: '/100%');

        $this->assertSame('/100%25', $url->path());
    }

    // --- empty userinfo ---

    public function testEmptyUserInfoIsTreatedAsAbsent(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', user: '', pass: '');

        $this->assertNull($url->user());
        $this->assertNull($url->pass());
        $this->assertSame('https://example.com/', $url->toString());
    }
}
