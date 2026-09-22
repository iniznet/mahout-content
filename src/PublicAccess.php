<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

use Iniznet\Mahout\Content\Contracts\Permission;

/**
 * The permission for a genuinely public route.
 *
 * A public route still declares a permission; this is the declaration, not the
 * absence of one. Core's own idiom for this is '__return_true', and a callback
 * that forgot its permission is indistinguishable from one that chose this.
 */
final readonly class PublicAccess implements Permission
{
    public function allowed(\WP_REST_Request $request): bool
    {
        return true;
    }
}
