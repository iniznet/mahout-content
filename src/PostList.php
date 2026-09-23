<?php

/**
 * One mapped page of a listing: the cards a Surface renders and the peek's
 * verdict. hasMore is not a count, it is the row the query asked for beyond
 * the page, so no pagination count query is ever issued.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

final readonly class PostList
{
    /**
     * @param list<PostData> $items
     */
    public function __construct(
        public array $items,
        public bool $hasMore,
    ) {
    }
}
