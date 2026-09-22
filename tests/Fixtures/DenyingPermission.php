<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Fixtures;

use Iniznet\Mahout\Content\Contracts\Permission;

/**
 * A permission that always refuses: the denial half of the seam.
 *
 * @internal
 */
final class DenyingPermission implements Permission
{
    public function allowed(\WP_REST_Request $request): bool
    {
        return false;
    }
}
