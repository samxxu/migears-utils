<?php

declare(strict_types=1);

namespace MiGears\Utils\Tests;

use PHPUnit\Framework\TestCase;
use MiGears\Utils\Paginator;

class PaginatorTest extends TestCase
{
    // --- Construction and defaults ---

    public function testDefaultValues(): void
    {
        $paginator = new Paginator();

        $this->assertSame(10, $paginator->pageSize);
        $this->assertSame(1, $paginator->currentPage);
        $this->assertSame(0, $paginator->total);
        $this->assertSame([], $paginator->items);
    }

    public function testConstructWithValues(): void
    {
        $items = ['a', 'b', 'c'];
        $paginator = new Paginator(
            pageSize: 20,
            currentPage: 3,
            total: 100,
            items: $items,
        );

        $this->assertSame(20, $paginator->pageSize);
        $this->assertSame(3, $paginator->currentPage);
        $this->assertSame(100, $paginator->total);
        $this->assertSame($items, $paginator->items);
    }

    // --- totalPages ---

    public function testTotalPagesZeroTotal(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 0);

        $this->assertSame(0, $paginator->totalPages());
    }

    public function testTotalPagesExactFit(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 50);

        $this->assertSame(5, $paginator->totalPages());
    }

    public function testTotalPagesRoundsUp(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 55);

        $this->assertSame(6, $paginator->totalPages());
    }

    public function testTotalPagesSingleItem(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 1);

        $this->assertSame(1, $paginator->totalPages());
    }

    // --- hasPrev / hasNext ---

    public function testHasPrevOnFirstPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 100);

        $this->assertFalse($paginator->hasPrev());
    }

    public function testHasPrevBeyondFirstPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 2, total: 100);

        $this->assertTrue($paginator->hasPrev());
    }

    public function testHasNextOnLastPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 10, total: 100);

        $this->assertFalse($paginator->hasNext());
    }

    public function testHasNextBeforeLastPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 5, total: 100);

        $this->assertTrue($paginator->hasNext());
    }

    public function testHasNextWithEmptyTotal(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 0);

        $this->assertFalse($paginator->hasNext());
    }

    // --- prevPage / nextPage ---

    public function testPrevPageReturnsNullOnFirstPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 100);

        $this->assertNull($paginator->prevPage());
    }

    public function testPrevPageReturnsPreviousNumber(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 5, total: 100);

        $this->assertSame(4, $paginator->prevPage());
    }

    public function testNextPageReturnsNullOnLastPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 10, total: 100);

        $this->assertNull($paginator->nextPage());
    }

    public function testNextPageReturnsNextNumber(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 5, total: 100);

        $this->assertSame(6, $paginator->nextPage());
    }

    // --- firstPage / lastPage ---

    public function testFirstPageAlwaysOne(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 5, total: 100);

        $this->assertSame(1, $paginator->firstPage());
    }

    public function testLastPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 55);

        $this->assertSame(6, $paginator->lastPage());
    }

    // --- offset ---

    public function testOffsetFirstPage(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 1, total: 100);

        $this->assertSame(0, $paginator->offset());
    }

    public function testOffsetPageThree(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 3, total: 100);

        $this->assertSame(20, $paginator->offset());
    }

    // --- count / isEmpty ---

    public function testCount(): void
    {
        $paginator = new Paginator(items: ['a', 'b', 'c']);

        $this->assertSame(3, $paginator->count());
    }

    public function testIsEmptyTrue(): void
    {
        $paginator = new Paginator();

        $this->assertTrue($paginator->isEmpty());
    }

    public function testIsEmptyFalse(): void
    {
        $paginator = new Paginator(items: ['a']);

        $this->assertFalse($paginator->isEmpty());
    }

    // --- withItems ---

    public function testWithItemsReturnsNewInstance(): void
    {
        $paginator = new Paginator(pageSize: 10, currentPage: 2, total: 50);
        $items = ['x', 'y', 'z'];
        $newPaginator = $paginator->withItems($items);

        $this->assertNotSame($paginator, $newPaginator);
        $this->assertSame([], $paginator->items);
        $this->assertSame($items, $newPaginator->items);
        $this->assertSame(10, $newPaginator->pageSize);
        $this->assertSame(2, $newPaginator->currentPage);
        $this->assertSame(50, $newPaginator->total);
    }

    // --- toArray ---

    public function testToArray(): void
    {
        $paginator = new Paginator(
            pageSize: 10,
            currentPage: 2,
            total: 25,
            items: ['a', 'b'],
        );

        $array = $paginator->toArray();

        $this->assertSame([
            'pageSize' => 10,
            'currentPage' => 2,
            'total' => 25,
            'totalPages' => 3,
            'hasPrev' => true,
            'hasNext' => true,
            'items' => ['a', 'b'],
        ], $array);
    }

    public function testToArrayFirstPage(): void
    {
        $paginator = new Paginator(
            pageSize: 10,
            currentPage: 1,
            total: 5,
            items: ['a', 'b'],
        );

        $array = $paginator->toArray();

        $this->assertFalse($array['hasPrev']);
        $this->assertFalse($array['hasNext']);
        $this->assertSame(1, $array['totalPages']);
    }

    // --- input normalization ---

    public function testZeroPageSizeIsRaisedToOne(): void
    {
        $paginator = new Paginator(pageSize: 0, total: 10);

        $this->assertSame(1, $paginator->pageSize);
        $this->assertSame(10, $paginator->totalPages());
    }

    public function testNegativePageSizeIsRaisedToOne(): void
    {
        $paginator = new Paginator(pageSize: -5, total: 10);

        $this->assertSame(1, $paginator->pageSize);
    }

    public function testPageNumberBelowOneIsRaisedToOne(): void
    {
        $zero = new Paginator(currentPage: 0, pageSize: 10, total: 50);
        $negative = new Paginator(currentPage: -3, pageSize: 10, total: 50);

        $this->assertSame(1, $zero->currentPage);
        $this->assertSame(0, $zero->offset());
        $this->assertSame(1, $negative->currentPage);
        $this->assertSame(0, $negative->offset());
    }

    public function testFirstPageIsZeroWhenThereAreNoPages(): void
    {
        $paginator = new Paginator(pageSize: 10, total: 0);

        $this->assertSame(0, $paginator->firstPage());
        $this->assertSame(0, $paginator->lastPage());
        $this->assertSame(0, $paginator->totalPages());
    }
}
