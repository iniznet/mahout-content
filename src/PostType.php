<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

/**
 * A declarative post type.
 *
 * The key is validated at registration, not here, so the naming rule, the
 * reserved list and the collision check all live in one place -- the registrar.
 * The rewrite slug is declared the way WordPress declares it, in
 * $args['rewrite']['slug']: one source, which is also the value the doctor's
 * collision check compares across the declared content model.
 */
final readonly class PostType
{
    /**
     * @param array<string, mixed> $args the arguments passed to register_post_type()
     */
    public function __construct(
        public string $key,
        public array $args = [],
    ) {
    }
}
