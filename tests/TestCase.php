<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Tests;

use Iniznet\Mahout\Content\Internal\WordPressRegistrar;

/**
 * The base test case for this package.
 *
 * Core's WP_UnitTestCase already wraps each test in a transaction and provides
 * the factories. This class exists so every test extends one name and so the
 * in-memory registries a test touches are returned to their prior shape: the
 * post type and taxonomy registries and the REST server are globals, so the
 * per-test transaction rollback does not reset them.
 *
 * @internal
 */
abstract class TestCase extends \WP_UnitTestCase
{
    /** @var list<string> */
    private array $postTypes = [];

    /** @var list<string> */
    private array $taxonomies = [];

    protected function registrar(): WordPressRegistrar
    {
        return new WordPressRegistrar();
    }

    /**
     * How many callbacks are attached to a hook. The boot proof reads it
     * around the provider's boot() to show which hooks it touched.
     */
    protected function hookCount(string $tag): int
    {
        $hook = $GLOBALS['wp_filter'][$tag] ?? null;

        if (!$hook instanceof \WP_Hook) {
            return 0;
        }

        $total = 0;
        foreach ($hook->callbacks as $callbacks) {
            $total += count($callbacks);
        }

        return $total;
    }

    protected function trackPostType(string $key): void
    {
        $this->postTypes[] = $key;
    }

    protected function trackTaxonomy(string $key): void
    {
        $this->taxonomies[] = $key;
    }

    protected function tearDown(): void
    {
        foreach (array_reverse(array_unique($this->taxonomies)) as $key) {
            if (\taxonomy_exists($key)) {
                \unregister_taxonomy($key);
            }
        }

        foreach (array_reverse(array_unique($this->postTypes)) as $key) {
            if (\post_type_exists($key)) {
                \unregister_post_type($key);
            }
        }

        $this->taxonomies = [];
        $this->postTypes = [];
        unset($GLOBALS['wp_rest_server']);

        parent::tearDown();
    }
}
