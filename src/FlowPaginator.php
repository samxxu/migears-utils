<?php

declare(strict_types=1);

namespace MiGears\Utils;

/**
 * Cursor-based (flow) pagination data structure (DTO).
 *
 * Used for infinite-scroll style pagination where you fetch "N items after cursor X".
 * The cursor is typically the ID of the last item in the current set.
 *
 * @template T
 */
final class FlowPaginator
{
    public readonly int $pageSize;
    public readonly mixed $cursor;
    public readonly string $cursorColumn;

    /** @var list<T> */
    public readonly array $items;

    public readonly bool $hasMore;

    /**
     * $hasMore is forced to false when $items is empty — an empty page ends the
     * sequence. Reporting more pages while holding no cursor would send the
     * next fetch back to the start, since a null cursor means "from the top".
     *
     * @param int $pageSize Number of items per page
     * @param mixed $cursor Cursor value (typically last item's ID), null means "from start"
     * @param string $cursorColumn Name of the column used for cursor (e.g. "id")
     * @param list<T> $items Items on the current page
     * @param bool $hasMore Whether there are more items after this page
     */
    public function __construct(
        int $pageSize = 10,
        mixed $cursor = null,
        string $cursorColumn = 'id',
        array $items = [],
        bool $hasMore = false,
    ) {
        $this->pageSize = $pageSize;
        $this->cursor = $cursor;
        $this->cursorColumn = $cursorColumn;
        $this->items = $items;
        $this->hasMore = $hasMore && $items !== [];
    }

    /**
     * Create a first-page paginator (no cursor yet).
     *
     * @template U
     * @param int $pageSize
     * @param string $cursorColumn
     * @return self<U>
     */
    public static function first(int $pageSize = 10, string $cursorColumn = 'id'): self
    {
        return new self(
            pageSize: $pageSize,
            cursor: null,
            cursorColumn: $cursorColumn,
            items: [],
            hasMore: false,
        );
    }

    /**
     * Number of items on the current page.
     */
    public function count(): int
    {
        return count($this->items);
    }

    /**
     * Whether the current page is empty.
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * Whether this is the first page (no cursor).
     */
    public function isFirstPage(): bool
    {
        return $this->cursor === null;
    }

    /**
     * Get the cursor for the next page (the last item's cursor column value).
     *
     * Returns null if there are no items or no more pages.
     */
    public function nextCursor(): mixed
    {
        if ($this->isEmpty() || !$this->hasMore) {
            return null;
        }

        return self::cursorFrom($this->items[array_key_last($this->items)], $this->cursorColumn);
    }

    /**
     * Return a new FlowPaginator with the given items and hasMore flag.
     *
     * The cursor is preserved from the current instance.
     *
     * @template U
     * @param list<U> $items
     * @param bool $hasMore
     * @return self<U>
     */
    public function withItems(array $items, bool $hasMore = false): self
    {
        return new self(
            pageSize: $this->pageSize,
            cursor: $this->cursor,
            cursorColumn: $this->cursorColumn,
            items: $items,
            hasMore: $hasMore,
        );
    }

    /**
     * Return a new FlowPaginator advancing to the next cursor.
     *
     * @template U
     * @param list<U> $items
     * @param bool $hasMore
     * @return self<U>
     */
    public function nextPage(array $items, bool $hasMore = false): self
    {
        $nextCursor = $items === [] || !$hasMore
            ? null
            : self::cursorFrom($items[array_key_last($items)], $this->cursorColumn);

        return new self(
            pageSize: $this->pageSize,
            cursor: $nextCursor,
            cursorColumn: $this->cursorColumn,
            items: $items,
            hasMore: $hasMore,
        );
    }

    /**
     * Read the cursor value out of an item, which may be an array or an object.
     */
    private static function cursorFrom(mixed $item, string $column): mixed
    {
        return match (true) {
            is_array($item) => $item[$column] ?? null,
            is_object($item) => $item->{$column} ?? null,
            default => null,
        };
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array{pageSize: int, cursor: mixed, cursorColumn: string, hasMore: bool, items: list<T>}
     */
    public function toArray(): array
    {
        return [
            'pageSize' => $this->pageSize,
            'cursor' => $this->cursor,
            'cursorColumn' => $this->cursorColumn,
            'hasMore' => $this->hasMore,
            'items' => $this->items,
        ];
    }
}
