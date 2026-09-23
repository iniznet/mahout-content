<?php

/**
 * One content query, declared. The kind's own filters travel with the page
 * geometry and with the hardening flags as explicit values, so a consumer
 * that genuinely needs a softer query declares the deviation on the spec
 * instead of reaching around the reader.
 *
 * The hardening defaults are the contract of {@see PostReader}: no count
 * query (no found rows), no core meta or term caching (the reader primes in
 * one statement per store), sticky posts ignored, ids only. A consumer that
 * flips one of these owns the cost it turns on.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

final readonly class QuerySpec
{
    /**
     * @param array<string, mixed> $filters the kind's own WP_Query filters
     */
    public function __construct(
        public array $filters,
        public int $perPage = 10,
        public int $offset = 0,
        public bool $noFoundRows = true,
        public bool $updatePostMetaCache = false,
        public bool $updatePostTermCache = false,
        public bool $ignoreStickyPosts = true,
        public QueryFields $fields = QueryFields::Ids,
    ) {
    }

    /**
     * The same query answering different filters: the search swap's vars
     * under a kind's base shape, or a narrowed kind. Everything else —
     * page geometry and hardening — carries over unchanged.
     *
     * @param array<string, mixed> $filters
     */
    public function withFilters(array $filters): self
    {
        return new self(
            filters: $filters,
            perPage: $this->perPage,
            offset: $this->offset,
            noFoundRows: $this->noFoundRows,
            updatePostMetaCache: $this->updatePostMetaCache,
            updatePostTermCache: $this->updatePostTermCache,
            ignoreStickyPosts: $this->ignoreStickyPosts,
            fields: $this->fields,
        );
    }
}
