<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Integration;

use Iniznet\Mahout\Content\Exception\InvalidRestRoute;
use Iniznet\Mahout\Content\PublicAccess;
use Iniznet\Mahout\Content\RestErrorCode;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Tests\Fixtures\DenyingPermission;
use Iniznet\Mahout\Content\Tests\TestCase;

/**
 * A route registered through this package: the explicit permission_callback,
 * the package's one denial shape, and the refusals at registration time.
 *
 * @internal
 */
final class RestRouteTest extends TestCase
{
    public function testTheRegisteredRouteCarriesAnExplicitPermissionCallback(): void
    {
        $this->registrar()->registerRestRoute(new RestRoute(
            namespace: 'fixture/v1',
            route: 'things',
            args: ['methods' => 'GET', 'callback' => static fn (): array => ['served' => true]],
            permission: new PublicAccess(),
        ));

        $server = $this->freshServer();
        $route = $server->get_routes()['/fixture/v1/things'] ?? null;

        self::assertIsArray($route);
        self::assertArrayHasKey('permission_callback', $route[0] ?? []);
        self::assertIsCallable($route[0]['permission_callback']);
    }

    public function testAPublicRouteServesItsCallback(): void
    {
        $this->registrar()->registerRestRoute(new RestRoute(
            namespace: 'fixture/v1',
            route: 'things',
            args: ['methods' => 'GET', 'callback' => static fn (): array => ['served' => true]],
            permission: new PublicAccess(),
        ));

        $response = $this->freshServer()->dispatch(new \WP_REST_Request('GET', '/fixture/v1/things'));

        self::assertInstanceOf(\WP_REST_Response::class, $response);
        self::assertSame(200, $response->get_status());
        self::assertSame(['served' => true], $response->get_data());
    }

    public function testADeniedRequestReturnsThePackageErrorShape(): void
    {
        \wp_set_current_user(0);
        $this->registrar()->registerRestRoute(new RestRoute(
            namespace: 'fixture/v1',
            route: 'things',
            args: ['methods' => 'GET', 'callback' => static fn (): array => []],
            permission: new DenyingPermission(),
        ));

        $response = $this->freshServer()->dispatch(new \WP_REST_Request('GET', '/fixture/v1/things'));

        // dispatch() envelopes a denial through error_to_response(): the
        // response carries the stable code, the translated message and the
        // authorisation status in one shape.
        self::assertInstanceOf(\WP_REST_Response::class, $response);
        self::assertSame(401, $response->get_status());
        self::assertSame(RestErrorCode::Forbidden->value, $response->get_data()['code'] ?? null);
        self::assertSame(401, $response->get_data()['data']['status'] ?? null);
    }

    public function testADeniedRequestReturns403ForALoggedInUser(): void
    {
        \wp_set_current_user(self::factory()->user->create(['role' => 'subscriber']));
        $this->registrar()->registerRestRoute(new RestRoute(
            namespace: 'fixture/v1',
            route: 'things',
            args: ['methods' => 'GET', 'callback' => static fn (): array => []],
            permission: new DenyingPermission(),
        ));

        $response = $this->freshServer()->dispatch(new \WP_REST_Request('GET', '/fixture/v1/things'));

        self::assertInstanceOf(\WP_REST_Response::class, $response);
        self::assertSame(403, $response->get_status());
        self::assertSame(403, $response->get_data()['data']['status'] ?? null);
    }

    public function testABadNamespaceIsRefusedAtRegistration(): void
    {
        $this->expectException(InvalidRestRoute::class);
        $this->registrar()->registerRestRoute(new RestRoute('fixture', 'things', [], new PublicAccess()));
    }

    public function testAnUnversionedNamespaceIsRefusedAtRegistration(): void
    {
        $this->expectException(InvalidRestRoute::class);
        $this->registrar()->registerRestRoute(new RestRoute('fixture/one', 'things', [], new PublicAccess()));
    }

    public function testAnUppercaseSegmentIsRefusedAtRegistration(): void
    {
        $this->expectException(InvalidRestRoute::class);
        $this->registrar()->registerRestRoute(new RestRoute('fixture/v1', 'Things', [], new PublicAccess()));
    }

    public function testALeadingSlashSegmentIsRefusedAtRegistration(): void
    {
        $this->expectException(InvalidRestRoute::class);
        $this->registrar()->registerRestRoute(new RestRoute('fixture/v1', '/things', [], new PublicAccess()));
    }

    public function testADoubledSlashIsRefusedAtRegistration(): void
    {
        $this->expectException(InvalidRestRoute::class);
        $this->registrar()->registerRestRoute(new RestRoute('fixture/v1', 'things//items', [], new PublicAccess()));
    }

    public function testACapturedPlaceholderIsAnAcceptedSegment(): void
    {
        $this->expectNotToPerformAssertions();

        $this->registrar()->registerRestRoute(new RestRoute(
            namespace: 'fixture/v1',
            route: 'things/(?P<id>[\\d]+)',
            args: ['methods' => 'GET', 'callback' => static fn (): array => []],
            permission: new PublicAccess(),
        ));
    }

    public function testARouteMayNotCarryItsOwnPermissionCallback(): void
    {
        $this->expectException(InvalidRestRoute::class);
        $this->registrar()->registerRestRoute(new RestRoute(
            namespace: 'fixture/v1',
            route: 'things',
            args: ['permission_callback' => '__return_true'],
            permission: new PublicAccess(),
        ));
    }

    public function testADeniedRequestOverTheWireCarriesTheTranslatedMessage(): void
    {
        \wp_set_current_user(0);
        $this->registrar()->registerRestRoute(new RestRoute(
            namespace: 'fixture/v1',
            route: 'things',
            args: ['methods' => 'GET', 'callback' => static fn (): array => []],
            permission: new DenyingPermission(),
        ));

        $response = $this->freshServer()->dispatch(new \WP_REST_Request('GET', '/fixture/v1/things'));

        self::assertInstanceOf(\WP_REST_Response::class, $response);

        $message = (string) ($response->get_data()['message'] ?? '');

        self::assertNotSame('', $message);
        self::assertStringNotContainsString('Exception', $message);
        self::assertStringNotContainsString(__FILE__, $message);
        self::assertStringNotContainsString('SELECT', $message);
    }

    /**
     * A fresh server: rest_api_init fires inside rest_get_server() when the
     * global is empty, which is exactly the one moment this package's deferred
     * registrations run.
     */
    private function freshServer(): \WP_REST_Server
    {
        unset($GLOBALS['wp_rest_server']);

        $server = \rest_get_server();

        self::assertInstanceOf(\WP_REST_Server::class, $server);

        return $server;
    }
}
