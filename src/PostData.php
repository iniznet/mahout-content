<?php

/**
 * The post as the render sees it: a readonly value, mapped once at the
 * boundary, carrying no WP_* type and no field-layer key. A teaser's content
 * and terms are empty by declaration — the listing renders none of them.
 */

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

final readonly class PostData
{
    /**
     * @param list<PostTerm> $categories
     * @param list<PostTerm> $tags
     */
    public function __construct(
        public int $id,
        public string $title,
        public string $content,
        public string $excerpt,
        public string $permalink,
        public \DateTimeImmutable $publishedAt,
        public string $dateDisplay,
        public string $authorName,
        public string $authorUrl,
        public string $thumbnail,
        public array $categories,
        public array $tags,
        public int $pageCount,
        public int $page,
    ) {
    }
}
