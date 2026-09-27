<?php

declare(strict_types=1);

namespace MiGears\Utils;

use InvalidArgumentException;

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
     * Two invariants hold by construction.
     *
     * $pageSize is raised to its lowest valid value (1), the same rule
     * Paginator applies, so the two DTOs agree on what a page size means.
     *
     * $hasMore is forced to false when $items is empty — an empty page ends the
     * sequence. When $hasMore is true the last item must carry the cursor
     * column, because a null cursor means "from the start": reporting more pages
     * without a usable cursor would send the next fetch back to page one.
     *
     * @param int $pageSize Number of items per page
     * @param mixed $cursor Cursor value (typically last item's ID), null means "from start"
     * @param string $cursorColumn Name of the column used for cursor (e.g. "id")
     * @param list<T> $items Items on the current page
     * @param bool $hasMore Whether there are more items after this page
     * @throws InvalidArgumentException When $hasMore is true and the last item has no cursor value
     */
    public function __construct(
        int $pageSize = 10,
        mixed $cursor = null,
        string $cursorColumn = 'id',
        array $items = [],
        bool $hasMore = false,
    ) {
        $this->pageSize = max(1, $pageSize);
        $this->cursor = $cursor;
        $this->cursorColumn = $cursorColumn;
        $this->items = $items;
        $this->hasMore = $hasMore && $items !== [];

        if ($this->hasMore && self::cursorFrom($items[array_key_last($items)], $cursorColumn) === null) {
            throw new InvalidArgumentException(
                "Cannot report more pages: the last item has no \"{$cursorColumn}\" value, and a null cursor "
                . 'means "from the start", so the next fetch would restart the page. '
                . 'Keep the cursor column in the items, or pass hasMore: false.',
            );
        }
    }

    /**
     * Create a first-page paginator (no cursor yet).
     *
     * The item type is unknown until items arrive, so it starts as mixed.
     *
     * @return self<mixed>
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
     * Null only when there is no next page: hasMore implies a usable cursor,
     * because the constructor rejects items that cannot produce one.
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
     * @throws InvalidArgumentException When $hasMore is true and the last item has no cursor value
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
     * @throws InvalidArgumentException When $hasMore is true and the last item has no cursor value
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
