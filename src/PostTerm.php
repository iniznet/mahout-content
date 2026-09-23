<?php

/**
 * One taxonomy term as the render sees it: an id, a name, its taxonomy and
 * its archive link. A WP_Term never crosses into a Component.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

final readonly class PostTerm
{
    public function __construct(
        public int $id,
        public string $name,
        public string $taxonomy,
        public string $link,
    ) {
    }
}
