<?php

declare(strict_types=1);

namespace Fixtures\Architecture\Violations;

/**
 * The negative fixture for mahout.arch.restRequiresPermissionCallback.
 *
 * This file is analysed by the fixtures configuration only; it is never
 * loaded at runtime, and its registration call is never executed.
 */
final class RestRouteWithoutPermissionCallback
{
    public function register(): void
    {
        \register_rest_route('fixture/v1', 'things', [
            'methods' => 'GET',
            'callback' => static fn (): array => [],
        ]);
    }
}
