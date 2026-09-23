<?php

/**
 * One page of posts exactly as the query answered it: the rows and the peek's
 * verdict.
 *
 * This is the boundary a mapper reads. The posts are WordPress objects and
 * they are primed -- the post cache, the meta cache, the term cache of every
 * type present in the page and the cache of every author in it -- so a mapper
 * can reach each one without a further statement. Nothing here is rendered and
 * nothing here is sanitised for output: crossing into a DTO is the mapper's
 * job, and this object's existence is what lets a mapper do that in one pass.
 *
 * hasMore is the peek, never a count. The reader asked for one row more than
 * the page holds; that row is the answer, and no pagination count query is
 * ever issued ({@see PostReader}).
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

final readonly class PostPage
{
    /**
     * @param list<\WP_Post> $posts the page's rows, in query order, primed
     */
    public function __construct(
        public array $posts,
        public bool $hasMore,
    ) {
    }
}
