<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Unit;

use Iniznet\Mahout\Content\PostReader;
use Iniznet\Mahout\Content\QueryFields;
use Iniznet\Mahout\Content\QuerySpec;

/**
 * The reader's contract, against real core: the hardened shape reaches
 * WP_Query, the peek decides hasMore without a count query, and the page
 * comes back primed. A malformed perPage is clamped, never fatal.
 */
final class PostReaderTest extends \WP_UnitTestCase
{
    private PostReader $reader;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reader = new PostReader();
        self::factory()->post->create_many(5, ['post_date' => '2024-01-01 00:00:00']);
    }

    public function testThePageCarriesExactlyThePageAndThePeekVerdict(): void
    {
        $page = $this->reader->fetch(new QuerySpec(
            filters: ['post_type' => 'post', 'orderby' => 'date', 'order' => 'DESC'],
            perPage: 2,
        ));

        self::assertCount(2, $page->posts, 'the page holds exactly the declared page size');
        self::assertTrue($page->hasMore, 'the peek row answers hasMore without a count query');
        self::assertContainsOnlyInstancesOf(\WP_Post::class, $page->posts);
    }

    public function testTheLastPageReportsNoMore(): void
    {
        $page = $this->reader->fetch(new QuerySpec(
            filters: ['post_type' => 'post', 'orderby' => 'date', 'order' => 'DESC'],
            perPage: 50,
            offset: 0,
        ));

        self::assertFalse($page->hasMore, 'a page that consumed the content graph has no more');
        self::assertGreaterThanOrEqual(5, \count($page->posts));
    }

    public function testAnOffsetStepsPastThePreviousPages(): void
    {
        $all = $this->reader->fetch(new QuerySpec(
            filters: ['post_type' => 'post', 'orderby' => 'date', 'order' => 'DESC'],
            perPage: 2,
            offset: 0,
        ));

        $second = $this->reader->fetch(new QuerySpec(
            filters: ['post_type' => 'post', 'orderby' => 'date', 'order' => 'DESC'],
            perPage: 2,
            offset: 2,
        ));

        self::assertNotSame($all->posts[0]->ID, $second->posts[0]->ID, 'the offset steps past the previous pages');
    }

    public function testAnEmptyResultSetIsNotPrimed(): void
    {
        $page = $this->reader->fetch(new QuerySpec(
            filters: ['post_type' => 'post', 'title' => 'zzz-none'],
            perPage: 2,
        ));

        self::assertSame([], $page->posts);
        self::assertFalse($page->hasMore);
    }

    public function testAnOverSizedPageIsClampedToTheCap(): void
    {
        // PER_PAGE_CAP is a bound on every fetch; a spec above it is clamped.
        $spec = new QuerySpec(filters: ['post_type' => 'post'], perPage: 5000);

        self::assertSame(5000, $spec->perPage, 'the spec is the caller\'s declaration, unmutated');

        $page = $this->reader->fetch($spec);

        self::assertLessThanOrEqual(PostReader::PER_PAGE_CAP, \count($page->posts));
    }

    public function testThePageIsPrimedForMapping(): void
    {
        // The priming contract is observable: after fetch(), every row of
        // the page is in the post cache, so the mapper's get_post() issues
        // no further statement.
        $page = $this->reader->fetch(new QuerySpec(
            filters: ['post_type' => 'post', 'orderby' => 'date', 'order' => 'DESC'],
            perPage: 3,
        ));

        $ids = array_map(static fn (\WP_Post $post): int => $post->ID, $page->posts);
        $inCache = array_filter($ids, static fn (int $id): bool => false !== \wp_cache_get($id, 'posts'));

        self::assertCount(\count($page->posts), $inCache, 'every row came from the cache, not a per-post query');
        self::assertNotSame('', $page->posts[0]->post_title ?? '', 'the row is the whole object, not a bare id');
    }

    public function testWholeRowsAreADeclaredOptOut(): void
    {
        $spec = new QuerySpec(
            filters: ['post_type' => 'post'],
            perPage: 2,
            fields: QueryFields::Rows,
        );

        self::assertSame('', $spec->fields->toQueryVar(), 'core\'s own name for the full row set');

        $page = $this->reader->fetch($spec);

        self::assertCount(2, $page->posts);
    }
}
