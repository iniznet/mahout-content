<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Integration;

use Iniznet\Mahout\Content\Exception\InvalidFilterResult;
use Iniznet\Mahout\Content\Hooks;
use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\PublicAccess;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Taxonomy;
use Iniznet\Mahout\Content\Tests\TestCase;

/**
 * The four content filters fire with exactly the documented arguments, and a
 * filter that returns anything but an array stops the registration instead of
 * feeding core a degraded argument.
 *
 * @internal
 */
final class ContentFilterTest extends TestCase
{
    public function testPostTypeArgsFiresWithTheDocumentedArguments(): void
    {
        $captured = null;
        $filter = static function (array $args, string $postType, PostType $definition) use (&$captured): array {
            $captured = [$args, $postType, $definition];

            return $args;
        };
        \add_filter(Hooks::POST_TYPE_ARGS, $filter, 10, 3);

        try {
            $this->trackPostType('fixture_filtered');
            $definition = new PostType(key: 'fixture_filtered', args: ['public' => true]);
            $this->registrar()->registerPostType($definition);
        } finally {
            \remove_filter(Hooks::POST_TYPE_ARGS, $filter, 10);
        }

        self::assertSame(['public' => true], $captured[0] ?? null);
        self::assertSame('fixture_filtered', $captured[1] ?? null);
        self::assertSame($definition, $captured[2] ?? null);
    }

    public function testTaxonomyArgsFiresWithTheDocumentedArguments(): void
    {
        $captured = null;
        $filter = static function (array $args, string $taxonomy, Taxonomy $definition) use (&$captured): array {
            $captured = [$args, $taxonomy, $definition];

            return $args;
        };
        \add_filter(Hooks::TAXONOMY_ARGS, $filter, 10, 3);

        try {
            $this->trackTaxonomy('fixture_filtered_genre');
            $definition = new Taxonomy(key: 'fixture_filtered_genre', objectTypes: ['post'], args: ['public' => true]);
            $this->registrar()->registerTaxonomy($definition);
        } finally {
            \remove_filter(Hooks::TAXONOMY_ARGS, $filter, 10);
        }

        self::assertSame(['public' => true], $captured[0] ?? null);
        self::assertSame('fixture_filtered_genre', $captured[1] ?? null);
        self::assertSame($definition, $captured[2] ?? null);
    }

    public function testRestRouteArgsFiresWithTheDocumentedArguments(): void
    {
        $captured = null;
        $route = new RestRoute(namespace: 'fixture/v1', route: 'things', args: ['methods' => 'GET'], permission: new PublicAccess());
        $filter = static function (array $args, RestRoute $routeDefinition) use (&$captured): array {
            $captured = [$args, $routeDefinition];

            return $args;
        };
        \add_filter(Hooks::REST_ROUTE_ARGS, $filter, 10, 2);

        try {
            $this->registrar()->registerRestRoute($route);
        } finally {
            \remove_filter(Hooks::REST_ROUTE_ARGS, $filter, 10);
        }

        self::assertSame(['methods' => 'GET'], $captured[0] ?? null);
        self::assertSame($route, $captured[1] ?? null);
    }

    public function testRestPayloadFiresWithTheDocumentedArguments(): void
    {
        $captured = null;
        $filter = static function (array $payload, object $object, RestRoute $route) use (&$captured): array {
            $captured = [$payload, $object, $route];

            return [...$payload, 'via' => 'filter'];
        };
        \add_filter(Hooks::REST_PAYLOAD, $filter, 10, 3);

        $route = new RestRoute('fixture/v1', 'things', [], new PublicAccess());
        $object = new \stdClass();

        try {
            $result = $this->registrar()->payload(['id' => 5], $object, $route);
        } finally {
            \remove_filter(Hooks::REST_PAYLOAD, $filter, 10);
        }

        self::assertSame(['id' => 5, 'via' => 'filter'], $result);
        self::assertSame(['id' => 5], $captured[0] ?? null);
        self::assertSame($object, $captured[1] ?? null);
        self::assertSame($route, $captured[2] ?? null);
    }

    public function testAPostTypeArgsFilterReturningAScalarStopsTheRegistration(): void
    {
        $refusing = static fn (): string => 'not an array';
        \add_filter(Hooks::POST_TYPE_ARGS, $refusing, 10, 3);

        try {
            $this->expectException(InvalidFilterResult::class);
            $this->registrar()->registerPostType(new PostType(key: 'fixture_filtered'));
        } finally {
            \remove_filter(Hooks::POST_TYPE_ARGS, $refusing, 10);
        }
    }

    public function testATaxonomyArgsFilterReturningAScalarStopsTheRegistration(): void
    {
        $refusing = static fn (): string => 'not an array';
        \add_filter(Hooks::TAXONOMY_ARGS, $refusing, 10, 3);

        try {
            $this->expectException(InvalidFilterResult::class);
            $this->registrar()->registerTaxonomy(new Taxonomy(key: 'fixture_genre_scalar', objectTypes: ['post']));
        } finally {
            \remove_filter(Hooks::TAXONOMY_ARGS, $refusing, 10);
        }
    }

    public function testARestRouteArgsFilterReturningAScalarStopsTheRegistration(): void
    {
        $refusing = static fn (): string => 'not an array';
        \add_filter(Hooks::REST_ROUTE_ARGS, $refusing, 10, 2);

        try {
            $this->expectException(InvalidFilterResult::class);
            $this->registrar()->registerRestRoute(new RestRoute('fixture/v1', 'things', [], new PublicAccess()));
        } finally {
            \remove_filter(Hooks::REST_ROUTE_ARGS, $refusing, 10);
        }
    }

    public function testAPayloadFilterReturningAScalarIsRefused(): void
    {
        $refusing = static fn (): string => 'not an array';
        \add_filter(Hooks::REST_PAYLOAD, $refusing, 10, 3);

        try {
            $this->expectException(InvalidFilterResult::class);
            $this->registrar()->payload([], new \stdClass(), new RestRoute('fixture/v1', 'things', [], new PublicAccess()));
        } finally {
            \remove_filter(Hooks::REST_PAYLOAD, $refusing, 10);
        }
    }
}
