<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

use Iniznet\Mahout\Content\Contracts\Registrar;
use Iniznet\Mahout\Content\Internal\WordPressRegistrar;
use Iniznet\Mahout\Kernel\Container;
use Iniznet\Mahout\Kernel\Contracts\ServiceProvider;

/**
 * The composition-root entry point for mahout-content.
 *
 * A provider is instantiated by class name with no constructor arguments, the
 * same shape as every provider in the family. It declares one service, the
 * WordPress-backed registrar, under the Contracts interface a consumer depends
 * on, so a module resolves the contract and never names an Internal class.
 *
 * boot() attaches nothing: the consumer's modules drive every registration by
 * calling the Registrar, and no registration happens at file scope.
 */
final class ContentProvider implements ServiceProvider
{
    public function register(Container $container): void
    {
        $container->set(service: new WordPressRegistrar(), id: Registrar::class);
    }

    public function boot(Container $container): void
    {
        // No hooks of its own. Every registration is an explicit call from a
        // consumer's module; nothing is attached here.
    }
}
