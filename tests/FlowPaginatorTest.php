<?php

declare(strict_types=1);

namespace MiGears\Utils\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Utils\FlowPaginator;

class FlowPaginatorTest extends TestCase
{
    // --- Construction and defaults ---

    public function testDefaultValues(): void
    {
        $paginator = new FlowPaginator();

        $this->assertSame(10, $paginator->pageSize);
        $this->assertNull($paginator->cursor);
        $this->assertSame('id', $paginator->cursorColumn);
        $this->assertSame([], $paginator->items);
        $this->assertFalse($paginator->hasMore);
    }

    public function testConstructWithValues(): void
    {
        $items = [['id' => 1], ['id' => 2]];
        $paginator = new FlowPaginator(
            pageSize: 20,
            cursor: 5,
            cursorColumn: 'created_at',
            items: $items,
            hasMore: true,
        );

        $this->assertSame(20, $paginator->pageSize);
        $this->assertSame(5, $paginator->cursor);
        $this->assertSame('created_at', $paginator->cursorColumn);
        $this->assertSame($items, $paginator->items);
        $this->assertTrue($paginator->hasMore);
    }

    // --- first() ---

    public function testFirstCreatesInitialPaginator(): void
    {
        $paginator = FlowPaginator::first(pageSize: 15, cursorColumn: 'uuid');

        $this->assertSame(15, $paginator->pageSize);
        $this->assertNull($paginator->cursor);
        $this->assertSame('uuid', $paginator->cursorColumn);
        $this->assertSame([], $paginator->items);
        $this->assertFalse($paginator->hasMore);
    }

    // --- count / isEmpty ---

    public function testCount(): void
    {
        $paginator = new FlowPaginator(items: [['id' => 1], ['id' => 2], ['id' => 3]]);

        $this->assertSame(3, $paginator->count());
    }

    public function testIsEmptyTrue(): void
    {
        $paginator = new FlowPaginator();

        $this->assertTrue($paginator->isEmpty());
    }

    public function testIsEmptyFalse(): void
    {
        $paginator = new FlowPaginator(items: [['id' => 1]]);

        $this->assertFalse($paginator->isEmpty());
    }

    // --- isFirstPage ---

    public function testIsFirstPageWhenCursorNull(): void
    {
        $paginator = new FlowPaginator(cursor: null);

        $this->assertTrue($paginator->isFirstPage());
    }

    public function testIsFirstPageWhenCursorSet(): void
    {
        $paginator = new FlowPaginator(cursor: 10);

        $this->assertFalse($paginator->isFirstPage());
    }

    // --- nextCursor ---

    public function testNextCursorWithArrayItems(): void
    {
        $items = [
            ['id' => 101],
            ['id' => 102],
            ['id' => 103],
        ];

        $paginator = new FlowPaginator(
            pageSize: 3,
            cursor: 100,
            cursorColumn: 'id',
            items: $items,
            hasMore: true,
        );

        $this->assertSame(103, $paginator->nextCursor());
    }

    public function testNextCursorWithObjectItems(): void
    {
        $item1 = new \stdClass();
        $item1->id = 101;
        $item2 = new \stdClass();
        $item2->id = 102;

        $paginator = new FlowPaginator(
            pageSize: 2,
            cursor: 100,
            cursorColumn: 'id',
            items: [$item1, $item2],
            hasMore: true,
        );

        $this->assertSame(102, $paginator->nextCursor());
    }

    public function testNextCursorEmptyItemsReturnsNull(): void
    {
        $paginator = new FlowPaginator(
            cursor: 100,
            items: [],
            hasMore: true,
        );

        $this->assertNull($paginator->nextCursor());
    }

    public function testNextCursorNoMoreReturnsNull(): void
    {
        $paginator = new FlowPaginator(
            cursor: 100,
            items: [['id' => 101]],
            hasMore: false,
        );

        $this->assertNull($paginator->nextCursor());
    }

    public function testNextCursorCustomColumn(): void
    {
        $items = [
            ['uuid' => 'aaa'],
            ['uuid' => 'bbb'],
        ];

        $paginator = new FlowPaginator(
            cursorColumn: 'uuid',
            items: $items,
            hasMore: true,
        );

        $this->assertSame('bbb', $paginator->nextCursor());
    }

    // --- withItems ---

    public function testWithItemsReturnsNewInstance(): void
    {
        $paginator = new FlowPaginator(pageSize: 20, cursor: 5, cursorColumn: 'id');
        $items = [['id' => 6], ['id' => 7]];
        $newPaginator = $paginator->withItems($items, true);

        $this->assertNotSame($paginator, $newPaginator);
        $this->assertSame([], $paginator->items);
        $this->assertFalse($paginator->hasMore);
        $this->assertSame($items, $newPaginator->items);
        $this->assertTrue($newPaginator->hasMore);
        $this->assertSame(20, $newPaginator->pageSize);
        $this->assertSame(5, $newPaginator->cursor); // cursor preserved
        $this->assertSame('id', $newPaginator->cursorColumn);
    }

    // --- nextPage ---

    public function testNextPageAdvancesCursor(): void
    {
        $paginator = FlowPaginator::first(pageSize: 3);

        $items = [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3],
        ];

        $next = $paginator->nextPage($items, true);

        $this->assertSame(3, $next->cursor);
        $this->assertSame($items, $next->items);
        $this->assertTrue($next->hasMore);
    }

    public function testNextPageNoMoreHasNullCursor(): void
    {
        $paginator = FlowPaginator::first(pageSize: 5);

        $items = [
            ['id' => 1],
            ['id' => 2],
        ];

        $next = $paginator->nextPage($items, false);

        $this->assertNull($next->cursor);
        $this->assertFalse($next->hasMore);
    }

    public function testNextPageEmptyItems(): void
    {
        $paginator = FlowPaginator::first();
        $next = $paginator->nextPage([], false);

        $this->assertNull($next->cursor);
        $this->assertSame([], $next->items);
        $this->assertFalse($next->hasMore);
    }

    public function testNextPageWithObjects(): void
    {
        $paginator = FlowPaginator::first();

        $item = new \stdClass();
        $item->id = 42;

        $next = $paginator->nextPage([$item], true);

        $this->assertSame(42, $next->cursor);
    }

    // --- toArray ---

    public function testToArray(): void
    {
        $paginator = new FlowPaginator(
            pageSize: 10,
            cursor: 100,
            cursorColumn: 'id',
            items: [['id' => 101], ['id' => 102]],
            hasMore: true,
        );

        $array = $paginator->toArray();

        $this->assertSame([
            'pageSize' => 10,
            'cursor' => 100,
            'cursorColumn' => 'id',
            'hasMore' => true,
            'items' => [['id' => 101], ['id' => 102]],
        ], $array);
    }

    public function testToArrayFirstPage(): void
    {
        $paginator = FlowPaginator::first();
        $array = $paginator->toArray();

        $this->assertNull($array['cursor']);
        $this->assertFalse($array['hasMore']);
        $this->assertSame([], $array['items']);
    }

    // --- empty pages ---

    public function testEmptyItemsForceHasMoreOff(): void
    {
        $paginator = new FlowPaginator(items: [], hasMore: true);

        $this->assertFalse($paginator->hasMore);
        $this->assertNull($paginator->nextCursor());
    }

    public function testNextPageWithEmptyItemsAndHasMoreKeepsCursorNull(): void
    {
        $next = FlowPaginator::first(pageSize: 5)->nextPage([], true);

        $this->assertNull($next->cursor);
        $this->assertFalse($next->hasMore);
        $this->assertNull($next->nextCursor());
    }
}
