<?php

/**
 * The query mechanics every content listing shares, and the only reader of
 * WP_Query in the package. Every query is hardened -- ids only, no count
 * query, no core meta or term caching, sticky posts ignored -- and the peek
 * asks for one row more than the page shows, so hasMore is decided without a
 * pagination count query. Every result set is primed before it is returned:
 * one statement per store (posts, terms, users), never one per post.
 *
 * A consumer that needs different hardening declares it on the QuerySpec;
 * the reader executes what it is given and never widens a bounded query.
 * Search is not this class's concern: a search spec's filters carry the
 * mahout-db swap's query vars, and the reader runs them as any other query.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

final readonly class PostReader
{
    /** The declared per-request cap. Never -1, never unbounded. */
    public const int PER_PAGE_CAP = 50;

    /**
     * One primed page. The peek row is asked for here, not by the caller:
     * the caller declares the page size it renders, the reader owns the
     * peek, the slice and the priming.
     */
    public function fetch(QuerySpec $spec): PostPage
    {
        $perPage = \max(1, \min(self::PER_PAGE_CAP, $spec->perPage));
        $query = new \WP_Query([
            'post_status' => 'publish',
            'posts_per_page' => $perPage + 1,
            'offset' => $spec->offset,
            'no_found_rows' => $spec->noFoundRows,
            'update_post_meta_cache' => $spec->updatePostMetaCache,
            'update_post_term_cache' => $spec->updatePostTermCache,
            'ignore_sticky_posts' => $spec->ignoreStickyPosts,
            'fields' => $spec->fields->toQueryVar(),
        ] + $spec->filters);

        // The rows are ids under the hardened default and whole objects
        // under the declared Rows opt-out; both shapes resolve to the same
        // primed, whole-object page.
        $rows = $query->posts ?? [];

        $hasMore = \count($rows) > $perPage;
        $rows = \array_slice($rows, 0, $perPage);

        if ([] === $rows) {
            return new PostPage([], false);
        }

        $ids = [];

        foreach ($rows as $row) {
            if (\is_numeric($row)) {
                $ids[] = (int) $row;
            } elseif ($row instanceof \WP_Post) {
                $ids[] = (int) $row->ID;
            }
        }

        \_prime_post_caches($ids, false, true);
        \update_object_term_cache($ids, 'post');
        $this->primeAuthors($ids);

        $posts = [];

        foreach ($rows as $row) {
            $post = $row instanceof \WP_Post ? $row : \get_post((int) $row);

            if ($post instanceof \WP_Post) {
                $posts[] = $post;
            }
        }

        return new PostPage($posts, $hasMore);
    }

    /**
     * The authors of the page's rows, primed in one query.
     *
     * @param list<int> $ids
     */
    private function primeAuthors(array $ids): void
    {
        $authors = [];

        foreach ($ids as $id) {
            $post = \get_post($id);

            if ($post instanceof \WP_Post && (int) $post->post_author > 0) {
                $authors[(int) $post->post_author] = true;
            }
        }

        if ([] !== $authors) {
            \cache_users(\array_keys($authors));
        }
    }
}
