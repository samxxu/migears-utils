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

    public function testSchemeWithTrailingLineFeedIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL scheme');

        new URL("https\n", 'example.com');
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

    public function testPathAndFragmentSurviveARebuildButTheQueryIsFormEncoded(): void
    {
        // The documented boundary: percent escapes in a path or fragment are
        // kept verbatim, while the query is rebuilt form-encoded.
        $url = URL::parse('https://example.com/a%20b?c%20d=1&e=2#f%20g');

        $this->assertSame('/a%20b', $url->path());
        $this->assertSame('f%20g', $url->fragment());
        $this->assertSame('https://example.com/a%20b?c+d=1&e=2#f%20g', $url->toString());
    }

    public function testQueryPlusIsReadAsASpaceAndWrittenBackTheSame(): void
    {
        // Self-consistent, just not byte-preserving: + decodes to a space and
        // a space re-encodes to +.
        $url = URL::parse('https://example.com/?a=1+2');

        $this->assertSame(['a' => '1 2'], $url->query());
        $this->assertSame('https://example.com/?a=1+2', $url->toString());
    }

    // --- empty userinfo ---

    public function testEmptyUserInfoIsTreatedAsAbsent(): void
    {
        $url = new URL(scheme: 'https', host: 'example.com', user: '', pass: '');

        $this->assertNull($url->user());
        $this->assertNull($url->pass());
        $this->assertSame('https://example.com/', $url->toString());
    }

    // --- port range ---

    public function testPortBoundsAreAccepted(): void
    {
        $this->assertSame(1, (new URL('https', 'example.com', port: 1))->port());
        $this->assertSame(65535, (new URL('https', 'example.com', port: 65535))->port());
    }

    public function testPortZeroIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('between 1 and 65535');

        new URL('https', 'example.com', port: 0);
    }

    public function testNegativePortIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new URL('https', 'example.com', port: -1);
    }

    public function testPortAboveRangeIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new URL('https', 'example.com', port: 70000);
    }

    public function testWithPortValidatesToo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new URL('https', 'example.com'))->withPort(70000);
    }

    // --- host characters ---

    public function testHostWithCrlfIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL host');

        new URL('https', "example.com\r\nX-Injected: yes");
    }

    // A bare LF needs its own case: the CRLF forms above carry text after the
    // newline, so they fail on the character class and never reach the anchor.
    public function testHostWithTrailingLineFeedIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL host');

        new URL('https', "example.com\n");
    }

    public function testIpLiteralWithTrailingLineFeedIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid URL host');

        new URL('https', "[::1]\n");
    }

    public function testHostWithSpaceIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new URL('https', 'exa mple.com');
    }

    public function testHostWithPathSeparatorIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new URL('https', 'example.com/path');
    }

    public function testHostWithAtSignIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new URL('https', 'user@example.com');
    }

    public function testMalformedPercentEscapeInHostIsRejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new URL('https', 'example.com%zz');
    }

    public function testUnderscoreHostIsAccepted(): void
    {
        $url = new URL('https', 'exam_ple.com');

        $this->assertSame('https://exam_ple.com/', $url->toString());
    }

    public function testIpv6LiteralRoundTrips(): void
    {
        $original = 'https://[::1]:8080/path';

        $this->assertSame('[::1]', URL::parse($original)->host());
        $this->assertSame($original, URL::parse($original)->toString());
    }

    public function testWithHostValidatesToo(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new URL('https', 'example.com'))->withHost("evil\r\nX-Injected: yes");
    }

    public function testWithHostRejectsTrailingLineFeed(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new URL('https', 'example.com'))->withHost("example.com\n");
    }

    // --- null query values ---

    public function testConstructorDropsNullQueryValues(): void
    {
        $url = new URL('https', 'example.com', query: ['a' => null, 'b' => 1]);

        $this->assertSame(['b' => 1], $url->query());
        $this->assertSame('https://example.com/?b=1', $url->toString());
    }

    public function testQueryAndToStringAgreeAboutNulls(): void
    {
        $url = new URL('https', 'example.com', query: ['a' => null, 'b' => 1, 'c' => null]);

        // No key reported by query() may be missing from the built string.
        foreach (array_keys($url->query()) as $key) {
            $this->assertStringContainsString($key . '=', $url->toString());
        }
    }

    public function testWithQueryDeletesWithNull(): void
    {
        $url = new URL('https', 'example.com', query: ['a' => '1', 'b' => '2']);

        $this->assertSame(['b' => '2'], $url->withQuery(['a' => null])->query());
    }

    public function testDeletingTheLastParameterLeavesNoQuestionMark(): void
    {
        $url = new URL('https', 'example.com', query: ['a' => '1']);

        $this->assertSame([], $url->withQuery(['a' => null])->query());
        $this->assertSame('https://example.com/', $url->withQuery(['a' => null])->toString());
    }

    public function testNullQueryValueDoesNotSurviveAMerge(): void
    {
        $url = new URL('https', 'example.com', query: ['a' => '1']);

        // merge-then-normalise must not turn a delete back into a stored null
        $this->assertArrayNotHasKey('a', $url->withQuery(['a' => null, 'b' => '2'])->query());
    }

    public function testQueryKeepsPhpTypesWhileToStringSerializesThem(): void
    {
        $url = new URL('https', 'example.com', query: ['flag' => true, 'n' => 5]);

        // Two documented representations of one parameter set: query() is the
        // map as set, toString() is its query-string form.
        $this->assertSame(['flag' => true, 'n' => 5], $url->query());
        $this->assertSame('https://example.com/?flag=1&n=5', $url->toString());
    }

    public function testFalsyScalarsAreStillParameters(): void
    {
        // The counterpart of the null rule: false, 0 and '' are values, and
        // nothing else may be dropped along with null.
        $url = new URL('https', 'example.com', query: ['flag' => false, 'n' => 0, 's' => '']);

        $this->assertSame(['flag' => false, 'n' => 0, 's' => ''], $url->query());
        $this->assertSame('https://example.com/?flag=0&n=0&s=', $url->toString());
    }
}
