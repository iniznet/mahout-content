<?php

declare(strict_types=1);

namespace Fixtures\Architecture\Clean;

/**
 * The clean twin of the permission fixture: the same registration with the
 * explicit permission_callback key the architecture rule requires. A public
 * route declares '__return_true'-equivalent access; it never omits the key.
 */
final class RestRouteWithPermissionCallback
{
    public function register(): void
    {
        \register_rest_route('fixture/v1', 'things', [
            'methods' => 'GET',
            'callback' => static fn (): array => [],
            'permission_callback' => static fn (): bool => true,
        ]);
    }
}
