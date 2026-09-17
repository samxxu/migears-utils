<?php

declare(strict_types=1);

namespace MiGears\Utils\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use MiGears\Utils\Arr;

#[CoversClass(Arr::class)]
final class ArrTest extends TestCase
{
    // --- get ---

    public function testGetWithDirectKey(): void
    {
        $arr = ['a' => 1, 'b' => 2];
        self::assertSame(1, Arr::get($arr, 'a'));
        self::assertSame(2, Arr::get($arr, 'b'));
    }

    public function testGetWithMissingKeyReturnsDefault(): void
    {
        $arr = ['a' => 1];
        self::assertNull(Arr::get($arr, 'missing'));
        self::assertSame('fallback', Arr::get($arr, 'missing', 'fallback'));
    }

    public function testGetWithDotNotation(): void
    {
        $arr = ['user' => ['name' => 'Alice', 'email' => 'alice@example.com']];
        self::assertSame('Alice', Arr::get($arr, 'user.name'));
        self::assertSame('alice@example.com', Arr::get($arr, 'user.email'));
    }

    public function testGetWithDeepDotNotation(): void
    {
        $arr = ['a' => ['b' => ['c' => ['d' => 42]]]];
        self::assertSame(42, Arr::get($arr, 'a.b.c.d'));
    }

    public function testGetWithMissingNestedKeyReturnsDefault(): void
    {
        $arr = ['user' => ['name' => 'Alice']];
        self::assertNull(Arr::get($arr, 'user.email'));
        self::assertSame('x', Arr::get($arr, 'user.email.phone', 'x'));
    }

    public function testGetWithNullKeyReturnsArray(): void
    {
        $arr = ['a' => 1];
        self::assertSame($arr, Arr::get($arr, null));
    }

    // --- set ---

    public function testSetWithDirectKey(): void
    {
        $arr = [];
        Arr::set($arr, 'key', 'value');
        self::assertSame('value', $arr['key']);
    }

    public function testSetWithDotNotation(): void
    {
        $arr = [];
        Arr::set($arr, 'user.name', 'Alice');
        self::assertSame('Alice', $arr['user']['name']);
    }

    public function testSetOverwritesExisting(): void
    {
        $arr = ['a' => ['b' => 'old']];
        Arr::set($arr, 'a.b', 'new');
        self::assertSame('new', $arr['a']['b']);
    }

    public function testSetWithNullKeyReplacesEntireArray(): void
    {
        $arr = ['old' => true];
        Arr::set($arr, null, ['new' => false]);
        self::assertSame(['new' => false], $arr);
    }

    // --- has ---

    public function testHasWithDirectKey(): void
    {
        $arr = ['a' => 1];
        self::assertTrue(Arr::has($arr, 'a'));
        self::assertFalse(Arr::has($arr, 'b'));
    }

    public function testHasWithDotNotation(): void
    {
        $arr = ['user' => ['name' => 'Alice']];
        self::assertTrue(Arr::has($arr, 'user.name'));
        self::assertFalse(Arr::has($arr, 'user.email'));
        self::assertFalse(Arr::has($arr, 'admin.name'));
    }

    public function testHasWithNullKeyReturnsFalse(): void
    {
        self::assertFalse(Arr::has(['a' => 1], null));
    }

    public function testHasWithNullValueReturnsTrue(): void
    {
        $arr = ['a' => null];
        self::assertTrue(Arr::has($arr, 'a'));
    }

    // --- pluck ---

    public function testPluckFromArrays(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        self::assertSame(['Alice', 'Bob'], Arr::pluck($items, 'name'));
    }

    public function testPluckWithKey(): void
    {
        $items = [
            ['id' => 1, 'name' => 'Alice'],
            ['id' => 2, 'name' => 'Bob'],
        ];

        self::assertSame([1 => 'Alice', 2 => 'Bob'], Arr::pluck($items, 'name', 'id'));
    }

    public function testPluckFromObjects(): void
    {
        $obj1 = new class { public string $name = 'Alice'; };
        $obj2 = new class { public string $name = 'Bob'; };

        self::assertSame(['Alice', 'Bob'], Arr::pluck([$obj1, $obj2], 'name'));
    }

    // --- first ---

    public function testFirstReturnsFirstElement(): void
    {
        self::assertSame(1, Arr::first([1, 2, 3]));
    }

    public function testFirstWithCallback(): void
    {
        $result = Arr::first([1, 2, 3, 4], fn($v) => $v > 2);
        self::assertSame(3, $result);
    }

    public function testFirstWithEmptyArrayReturnsDefault(): void
    {
        self::assertNull(Arr::first([]));
        self::assertSame('nope', Arr::first([], null, 'nope'));
    }

    public function testFirstWithNoMatchReturnsDefault(): void
    {
        self::assertSame(0, Arr::first([1, 2, 3], fn($v) => $v > 10, 0));
    }

    // --- last ---

    public function testLastReturnsLastElement(): void
    {
        self::assertSame(3, Arr::last([1, 2, 3]));
    }

    public function testLastWithCallback(): void
    {
        $result = Arr::last([1, 2, 3, 4], fn($v) => $v < 4);
        self::assertSame(3, $result);
    }

    public function testLastWithEmptyArrayReturnsDefault(): void
    {
        self::assertNull(Arr::last([]));
        self::assertSame('nope', Arr::last([], null, 'nope'));
    }

    // --- where ---

    public function testWhereFilters(): void
    {
        $result = Arr::where([1, 2, 3, 4, 5], fn($v) => $v > 3);
        self::assertSame([4, 5], $result);
    }

    public function testWherePreservesKeys(): void
    {
        $result = Arr::where(['a' => 1, 'b' => 2, 'c' => 3], fn($v) => $v > 1, true);
        self::assertSame(['b' => 2, 'c' => 3], $result);
    }

    // --- only ---

    public function testOnlyReturnsWhitelistedKeys(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertSame(['a' => 1, 'c' => 3], Arr::only($arr, ['a', 'c']));
    }

    // --- except ---

    public function testExceptRemovesBlacklistedKeys(): void
    {
        $arr = ['a' => 1, 'b' => 2, 'c' => 3];
        self::assertSame(['a' => 1, 'c' => 3], Arr::except($arr, ['b']));
    }

    // --- flatten ---

    public function testFlattenOneLevel(): void
    {
        $arr = [[1, 2], [3, 4]];
        self::assertSame([1, 2, 3, 4], Arr::flatten($arr, 1));
    }

    public function testFlattenDeep(): void
    {
        $arr = [1, [2, [3, [4]]]];
        self::assertSame([1, 2, 3, 4], Arr::flatten($arr));
    }

    public function testFlattenWithMixed(): void
    {
        $arr = [1, 'a', [2, 'b'], [[3]]];
        self::assertSame([1, 'a', 2, 'b', 3], Arr::flatten($arr));
    }

    // --- collapse ---

    public function testCollapse(): void
    {
        $arr = [[1, 2], [3, 4]];
        self::assertSame([1, 2, 3, 4], Arr::collapse($arr));
    }

    // --- every ---

    public function testEveryAllPass(): void
    {
        self::assertTrue(Arr::every([2, 4, 6], fn($v) => $v % 2 === 0));
    }

    public function testEveryOneFails(): void
    {
        self::assertFalse(Arr::every([2, 3, 6], fn($v) => $v % 2 === 0));
    }

    public function testEveryEmptyArrayReturnsTrue(): void
    {
        self::assertTrue(Arr::every([], fn() => false));
    }

    // --- some ---

    public function testSomeAtLeastOnePasses(): void
    {
        self::assertTrue(Arr::some([1, 2, 3], fn($v) => $v === 2));
    }

    public function testSomeNonePass(): void
    {
        self::assertFalse(Arr::some([1, 2, 3], fn($v) => $v > 10));
    }

    public function testSomeEmptyArrayReturnsFalse(): void
    {
        self::assertFalse(Arr::some([], fn() => true));
    }

    // --- unique ---

    public function testUniqueRemovesDuplicates(): void
    {
        self::assertSame([1, 2, 3], Arr::unique([1, 2, 2, 3, 1]));
    }

    public function testUniquePreservesOrder(): void
    {
        self::assertSame(['a', 'b', 'c'], Arr::unique(['a', 'b', 'a', 'c', 'b']));
    }

    public function testUniqueWithStrictComparison(): void
    {
        self::assertSame([1, '1', 2], array_values(Arr::unique([1, '1', 2])));
    }

    // --- Version constant ---

    public function testVersionConstant(): void
    {
        self::assertSame('2.0.0', Arr::VERSION);
    }
}
