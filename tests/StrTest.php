<?php

declare(strict_types=1);

namespace MiGears\Utils\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Utils\Str;

class StrTest extends TestCase
{
    // --- startsWith ---

    public function testStartsWithTrue(): void
    {
        $this->assertTrue(Str::startsWith('hello world', 'hello'));
    }

    public function testStartsWithFalse(): void
    {
        $this->assertFalse(Str::startsWith('hello world', 'world'));
    }

    public function testStartsWithEmptyNeedle(): void
    {
        $this->assertTrue(Str::startsWith('hello', ''));
    }

    // --- endsWith ---

    public function testEndsWithTrue(): void
    {
        $this->assertTrue(Str::endsWith('hello world', 'world'));
    }

    public function testEndsWithFalse(): void
    {
        $this->assertFalse(Str::endsWith('hello world', 'hello'));
    }

    public function testEndsWithEmptyNeedle(): void
    {
        $this->assertTrue(Str::endsWith('hello', ''));
    }

    // --- contains ---

    public function testContainsTrue(): void
    {
        $this->assertTrue(Str::contains('hello world', 'lo wo'));
    }

    public function testContainsFalse(): void
    {
        $this->assertFalse(Str::contains('hello world', 'foo'));
    }

    public function testContainsEmptyNeedle(): void
    {
        $this->assertTrue(Str::contains('hello', ''));
    }

    // --- length ---

    public function testLengthAscii(): void
    {
        $this->assertSame(5, Str::length('hello'));
    }

    public function testLengthMultibyte(): void
    {
        $this->assertSame(5, Str::length('你好世界！'));
    }

    public function testLengthEmptyString(): void
    {
        $this->assertSame(0, Str::length(''));
    }

    // --- slug ---

    public function testSlugBasic(): void
    {
        $this->assertSame('hello-world', Str::slug('Hello World!'));
    }

    public function testSlugWithSpaces(): void
    {
        $this->assertSame('foo-bar-baz', Str::slug('  foo   bar  baz  '));
    }

    public function testSlugWithCustomSeparator(): void
    {
        $this->assertSame('hello_world', Str::slug('Hello World', '_'));
    }

    public function testSlugRemovesSpecialChars(): void
    {
        $this->assertSame('abc-123', Str::slug('ABC 123!@#'));
    }

    public function testSlugConvertsAccents(): void
    {
        $result = Str::slug('Café résumé');
        $this->assertSame('cafe-resume', $result);
    }

    public function testSlugEmptyString(): void
    {
        $this->assertSame('', Str::slug('!!!'));
    }

    // --- truncate ---

    public function testTruncateShortString(): void
    {
        $this->assertSame('hello', Str::truncate('hello', 10));
    }

    public function testTruncateLongString(): void
    {
        $this->assertSame('hel...', Str::truncate('hello world', 6));
    }

    public function testTruncateExactLength(): void
    {
        $this->assertSame('hello', Str::truncate('hello', 5));
    }

    public function testTruncateCustomEllipsis(): void
    {
        $this->assertSame('he…', Str::truncate('hello world', 3, '…'));
    }

    public function testTruncateMultibyte(): void
    {
        // Test text: "你好世界，今天天气真好！" = 12 chars
        $text = '你好世界，今天天气真好！';
        $this->assertSame(12, Str::length($text));

        // truncate to 5: 2 chars + ... = 5 total
        $this->assertSame('你好...', Str::truncate($text, 5));
        // truncate to 6: 3 chars + ... = 6 total
        $this->assertSame('你好世...', Str::truncate($text, 6));
    }

    public function testTruncateZeroLength(): void
    {
        $this->assertSame('...', Str::truncate('hello', 0));
    }

    // --- words ---

    public function testWordsWithinLimit(): void
    {
        $this->assertSame('hello world', Str::words('hello world', 10));
    }

    public function testWordsLimit(): void
    {
        $this->assertSame('hello world...', Str::words('hello world foo bar', 2));
    }

    public function testWordsCustomEnd(): void
    {
        $this->assertSame('hello world…', Str::words('hello world foo bar', 2, '…'));
    }

    public function testWordsExact(): void
    {
        $this->assertSame('one two three', Str::words('one two three', 3));
    }

    // --- camel / studly ---

    public function testCamel(): void
    {
        $this->assertSame('helloWorld', Str::camel('hello_world'));
    }

    public function testCamelFromKebab(): void
    {
        $this->assertSame('helloWorld', Str::camel('hello-world'));
    }

    public function testCamelFromSpaces(): void
    {
        $this->assertSame('helloWorld', Str::camel('hello world'));
    }

    public function testStudly(): void
    {
        $this->assertSame('HelloWorld', Str::studly('hello_world'));
    }

    public function testStudlyFromKebab(): void
    {
        $this->assertSame('HelloWorld', Str::studly('hello-world'));
    }

    // --- snake / kebab ---

    public function testSnake(): void
    {
        $this->assertSame('hello_world', Str::snake('helloWorld'));
    }

    public function testSnakeFromStudly(): void
    {
        $this->assertSame('hello_world', Str::snake('HelloWorld'));
    }

    public function testSnakeFromKebab(): void
    {
        $this->assertSame('hello_world', Str::snake('hello-world'));
    }

    public function testKebab(): void
    {
        $this->assertSame('hello-world', Str::kebab('helloWorld'));
    }

    public function testKebabFromStudly(): void
    {
        $this->assertSame('hello-world', Str::kebab('HelloWorld'));
    }

    // --- ucfirst / lcfirst ---

    public function testUcfirst(): void
    {
        $this->assertSame('Hello', Str::ucfirst('hello'));
    }

    public function testUcfirstMultibyte(): void
    {
        $this->assertSame('Ñoño', Str::ucfirst('ñoño'));
    }

    public function testLcfirst(): void
    {
        $this->assertSame('hello', Str::lcfirst('Hello'));
    }

    public function testLcfirstMultibyte(): void
    {
        $this->assertSame('ñoño', Str::lcfirst('Ñoño'));
    }

    // --- ascii ---

    public function testAsciiAccentedChars(): void
    {
        $this->assertSame('cafe', Str::ascii('café'));
    }

    public function testAsciiAlreadyAscii(): void
    {
        $this->assertSame('hello', Str::ascii('hello'));
    }

    // --- random ---

    public function testRandomLength(): void
    {
        $result = Str::random(16);
        $this->assertSame(16, strlen($result));
    }

    public function testRandomIsAlphanumeric(): void
    {
        $result = Str::random(32);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_-]+$/', $result);
    }

    public function testRandomTwoCallsDiffer(): void
    {
        $a = Str::random(32);
        $b = Str::random(32);
        $this->assertNotSame($a, $b);
    }

    public function testRandomLengthOne(): void
    {
        $result = Str::random(1);
        $this->assertSame(1, strlen($result));
    }

    public function testRandomInvalidLengthThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Str::random(0);
    }

    // --- reverse ---

    public function testReverseAscii(): void
    {
        $this->assertSame('olleh', Str::reverse('hello'));
    }

    public function testReverseMultibyte(): void
    {
        $this->assertSame('！界世好你', Str::reverse('你好世界！'));
    }

    public function testReverseEmptyString(): void
    {
        $this->assertSame('', Str::reverse(''));
    }

    public function testReverseSingleChar(): void
    {
        $this->assertSame('a', Str::reverse('a'));
    }
}
