<?php

declare(strict_types=1);

namespace MiGears\Utils;

/**
 * Standard offset-based pagination data structure (DTO).
 *
 * Pure data object — no URL building, no template rendering.
 *
 * @template T
 */
final class Paginator
{
    public readonly int $pageSize;
    public readonly int $currentPage;
    public readonly int $total;

    /** @var list<T> */
    public readonly array $items;

    /**
     * $pageSize and $currentPage are raised to their lowest valid value (1),
     * so a page size of 0 cannot divide by zero and a page number below 1
     * cannot produce a negative offset.
     *
     * @param int $pageSize Number of items per page
     * @param int $currentPage Current page number (1-based)
     * @param int $total Total number of items across all pages
     * @param list<T> $items Items on the current page
     */
    public function __construct(
        int $pageSize = 10,
        int $currentPage = 1,
        int $total = 0,
        array $items = [],
    ) {
        $this->pageSize = max(1, $pageSize);
        $this->currentPage = max(1, $currentPage);
        $this->total = $total;
        $this->items = $items;
    }

    /**
     * Total number of pages.
     */
    public function totalPages(): int
    {
        if ($this->total <= 0) {
            return 0;
        }

        return (int) ceil($this->total / $this->pageSize);
    }

    /**
     * Whether there is a previous page.
     */
    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    /**
     * Whether there is a next page.
     */
    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages();
    }

    /**
     * Previous page number, or null if on first page.
     */
    public function prevPage(): ?int
    {
        return $this->hasPrev() ? $this->currentPage - 1 : null;
    }

    /**
     * Next page number, or null if on last page.
     */
    public function nextPage(): ?int
    {
        return $this->hasNext() ? $this->currentPage + 1 : null;
    }

    /**
     * First page number, or 0 when there are no pages.
     */
    public function firstPage(): int
    {
        return $this->totalPages() === 0 ? 0 : 1;
    }

    /**
     * Last page number, or 0 if no items.
     */
    public function lastPage(): int
    {
        return $this->totalPages();
    }

    /**
     * 0-based offset for database queries.
     */
    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->pageSize;
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
     * Return a new Paginator with the given items.
     *
     * @template U
     * @param list<U> $items
     * @return self<U>
     */
    public function withItems(array $items): self
    {
        return new self(
            pageSize: $this->pageSize,
            currentPage: $this->currentPage,
            total: $this->total,
            items: $items,
        );
    }

    /**
     * Convert to array for JSON serialization.
     *
     * @return array{pageSize: int, currentPage: int, total: int, totalPages: int, hasPrev: bool, hasNext: bool, items: list<T>}
     */
    public function toArray(): array
    {
        return [
            'pageSize' => $this->pageSize,
            'currentPage' => $this->currentPage,
            'total' => $this->total,
            'totalPages' => $this->totalPages(),
            'hasPrev' => $this->hasPrev(),
            'hasNext' => $this->hasNext(),
            'items' => $this->items,
        ];
    }
}
