<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Unit;

use Iniznet\Mahout\Content\PublicAccess;
use Iniznet\Mahout\Content\Tests\TestCase;

/**
 * The declaration of a genuinely public route. A public route still declares
 * a permission; this is the declaration, not the absence of one.
 *
 * @internal
 */
final class PublicAccessTest extends TestCase
{
    public function testAPublicRouteAllowsTheRequest(): void
    {
        self::assertTrue((new PublicAccess())->allowed(new \WP_REST_Request('GET', '/fixture/v1/things')));
    }
}
