<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Contracts;

/**
 * The authorisation seam for a REST route.
 *
 * An implementation decides only whether a request is allowed. The registrar
 * wraps the answer, so every denied request has one error shape rather than one
 * per route.
 */
interface Permission
{
    public function allowed(\WP_REST_Request $request): bool;
}
