<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

/**
 * A declarative taxonomy.
 *
 * The key is validated at registration. The object types are the post types the
 * taxonomy is attached to, named explicitly rather than discovered.
 */
final readonly class Taxonomy
{
    /**
     * @param list<string>         $objectTypes the post types the taxonomy is attached to
     * @param array<string, mixed> $args        the arguments passed to register_taxonomy()
     */
    public function __construct(
        public string $key,
        public array $objectTypes,
        public array $args = [],
    ) {
    }
}
