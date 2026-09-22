<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests\Contract;

use Iniznet\Mahout\Content\ContentProvider;
use Iniznet\Mahout\Content\Contracts\Registrar;
use Iniznet\Mahout\Content\Hooks;
use Iniznet\Mahout\Content\Tests\TestCase;
use Iniznet\Mahout\Kernel\Container;

/**
 * The provider is the composition-root entry point: the registrar is declared
 * under the Contracts interface a consumer depends on, and boot attaches
 * nothing, because every registration is an explicit call from a consumer's
 * module.
 *
 * @internal
 */
final class RegistrarContractTest extends TestCase
{
    public function testTheProviderDeclaresTheRegistrarUnderTheContract(): void
    {
        $container = new Container();

        (new ContentProvider())->register($container);

        self::assertTrue($container->has(Registrar::class));
        self::assertInstanceOf(Registrar::class, $container->get(Registrar::class));
    }

    public function testTheProviderAttachesNothingAtBoot(): void
    {
        $container = new Container();
        $provider = new ContentProvider();
        $provider->register($container);

        $beforeRest = $this->hookCount(Hooks::REST_API_INIT);
        $beforeInit = $this->hookCount('init');
        $provider->boot($container);

        self::assertSame(0, $this->hookCount(Hooks::REST_API_INIT) - $beforeRest);
        self::assertSame(0, $this->hookCount('init') - $beforeInit);
    }

    public function testARegistrationThroughTheContractIsVisibleToCore(): void
    {
        $container = new Container();
        (new ContentProvider())->register($container);

        $registrar = $container->get(Registrar::class);
        $registrar->registerPostType(new \Iniznet\Mahout\Content\PostType(
            key: 'fixture_contract',
            args: ['public' => true],
        ));

        $this->trackPostType('fixture_contract');

        self::assertTrue(\post_type_exists('fixture_contract'));
    }
}
