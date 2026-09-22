<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

use Iniznet\Mahout\Content\Contracts\Permission;

/**
 * A declarative REST route.
 *
 * The namespace carries the version, per the REST contract. The permission is a
 * collaborator rather than a callback string, so the route cannot be registered
 * without one and the registrar can wrap the denial in the package's one error
 * shape.
 */
final readonly class RestRoute
{
    /**
     * @param array<string, mixed> $args the route arguments, without permission_callback
     */
    public function __construct(
        public string $namespace,
        public string $route,
        public array $args,
        public Permission $permission,
    ) {
    }
}
