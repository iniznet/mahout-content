<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Unit;

use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\RewriteSlugs;
use Iniznet\Mahout\Content\Taxonomy;
use Iniznet\Mahout\Content\Tests\TestCase;

/**
 * The rewrite collision computation, over definitions only: no WordPress
 * function is touched, which is what makes the doctor check provable in a
 * unit test.
 *
 * @internal
 */
final class RewriteSlugsTest extends TestCase
{
    public function testTwoDefinitionsClaimingOneSlugCollide(): void
    {
        $slugs = new RewriteSlugs([
            new PostType(key: 'fixture_series', args: ['rewrite' => ['slug' => 'series']]),
            new Taxonomy(key: 'fixture_genre', objectTypes: ['fixture_series'], args: ['rewrite' => ['slug' => 'series']]),
        ]);

        $collisions = $slugs->collisions();

        self::assertCount(1, $collisions);
        self::assertSame('series', $collisions[0]->slug);
        self::assertSame(['post type fixture_series', 'taxonomy fixture_genre'], $collisions[0]->declaredBy);
    }

    public function testDistinctSlugsDoNotCollide(): void
    {
        $slugs = new RewriteSlugs([
            new PostType(key: 'fixture_series', args: ['rewrite' => ['slug' => 'series']]),
            new Taxonomy(key: 'fixture_genre', objectTypes: ['fixture_series'], args: ['rewrite' => ['slug' => 'genre']]),
        ]);

        self::assertSame([], $slugs->collisions());
    }

    public function testADisabledRewriteClaimsNothing(): void
    {
        $slugs = new RewriteSlugs([
            new PostType(key: 'fixture_series', args: ['rewrite' => false]),
            new Taxonomy(key: 'fixture_series_tax', objectTypes: ['fixture_series'], args: ['rewrite' => ['slug' => 'fixture_series']]),
        ]);

        self::assertSame([], $slugs->collisions());
    }

    public function testTheDefaultSlugIsTheDefinitionsOwnKey(): void
    {
        $slugs = new RewriteSlugs([
            new PostType(key: 'fixture_series'),
            new Taxonomy(key: 'fixture_other', objectTypes: ['fixture_series'], args: ['rewrite' => true]),
        ]);

        $collisions = $slugs->collisions();

        // rewrite => true falls back to each definition's key: 'fixture_series'
        // and 'fixture_other' differ, so nothing collides.
        self::assertSame([], $collisions);
    }

    public function testTwoKeysWithTheSameDefaultSlugCollide(): void
    {
        // Both definitions fall back to their own key; a post type and a
        // taxonomy sharing one key is exactly the collision the rule exists for.
        $slugs = new RewriteSlugs([
            new PostType(key: 'fixture_series', args: ['rewrite' => true]),
            new Taxonomy(key: 'fixture_series', objectTypes: ['fixture_series'], args: []),
        ]);

        $collisions = $slugs->collisions();

        self::assertCount(1, $collisions);
        self::assertSame('fixture_series', $collisions[0]->slug);
    }

    public function testCollisionsAreOrderedBySlug(): void
    {
        $slugs = new RewriteSlugs([
            new PostType(key: 'fixture_two', args: ['rewrite' => ['slug' => 'zeta']]),
            new Taxonomy(key: 'fixture_two_tax', objectTypes: [], args: ['rewrite' => ['slug' => 'zeta']]),
            new PostType(key: 'fixture_one', args: ['rewrite' => ['slug' => 'alpha']]),
            new Taxonomy(key: 'fixture_two_two', objectTypes: [], args: ['rewrite' => ['slug' => 'alpha']]),
        ]);

        $collisions = $slugs->collisions();

        self::assertSame(['alpha', 'zeta'], [$collisions[0]->slug, $collisions[1]->slug]);
    }
}
