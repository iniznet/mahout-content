<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Contracts;

use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Taxonomy;

/**
 * The content-model registration surface.
 *
 * A consumer depends on this interface and receives the WordPress-backed
 * implementation from the container, so the registration call sites name the
 * contract rather than a concrete class.
 */
interface Registrar
{
    public function registerPostType(PostType $definition): void;

    public function registerTaxonomy(Taxonomy $definition): void;

    public function registerRestRoute(RestRoute $definition): void;

    /**
     * Applies the payload filter to a REST resource before it is served.
     *
     * The payload is the resource a Mapper produced: a name-to-value map for a
     * single resource, or a list for a collection, and a filter may reshape it
     * but must return an array of either shape.
     *
     * @param array<array-key, mixed> $payload the mapped resource
     * @param object                  $object  the object the payload was mapped from
     *
     * @return array<array-key, mixed>
     */
    public function payload(array $payload, object $object, RestRoute $route): array;
}
