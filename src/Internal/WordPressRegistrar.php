<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content\Internal;

use Iniznet\Mahout\Content\Contracts\Registrar;
use Iniznet\Mahout\Content\Exception\ContentTypeCollision;
use Iniznet\Mahout\Content\Exception\InvalidContentTypeKey;
use Iniznet\Mahout\Content\Exception\InvalidFilterResult;
use Iniznet\Mahout\Content\Exception\InvalidRestRoute;
use Iniznet\Mahout\Content\Exception\ReservedContentTypeName;
use Iniznet\Mahout\Content\Hooks;
use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\RestError;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Taxonomy;

/**
 * The WordPress-backed registrar, and the only class in this package that
 * calls WordPress's registration functions.
 *
 * Every key rule runs here, before core sees anything, because core's own
 * behaviour on the same input is a silent, order-dependent overwrite:
 * register_post_type() lowercases a mixed-case key through sanitize_key() and
 * overwrites an existing registration, and register_taxonomy() does both too.
 * The naming convention, the length cap and the reserved list are therefore one
 * path in one place, and every refusal is a loud exception.
 *
 * Post types and taxonomies register immediately at the call site -- the kernel
 * boots before init, and WP_Rewrite already exists by then. The REST call is
 * the one core polices by time: register_rest_route() raises _doing_it_wrong()
 * before rest_api_init, so the route registers through a handler attached to
 * rest_api_init. One path per kind.
 *
 * A route's Permission is the only permission declaration. The registrar
 * injects the explicit permission_callback key core requires; a definition that
 * also carries one in its args is refused, because two ways to declare one
 * permission is one way too many.
 *
 * @internal
 */
final class WordPressRegistrar implements Registrar
{
    private const string KEY_PATTERN = '/^[a-z][a-z0-9]*_[a-z0-9_]+$/';

    private const int POST_TYPE_MAXIMUM = 20;

    private const int TAXONOMY_MAXIMUM = 32;

    /**
     * Core's own post type names. A reserved name is refused before the
     * collision check, so the report names the reservation and not an
     * order-dependent accident.
     *
     * @var list<string>
     */
    private const array RESERVED_POST_TYPES = [
        'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'custom_css',
        'customize_changeset', 'oembed_cache', 'user_request', 'wp_block',
        'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation',
        'wp_font_family', 'wp_font_face',
    ];

    /**
     * @var list<string>
     */
    private const array RESERVED_TAXONOMIES = [
        'category', 'post_tag', 'nav_menu', 'link_category', 'post_format',
    ];

    public function registerPostType(PostType $definition): void
    {
        $this->assertKey('post type', $definition->key, self::RESERVED_POST_TYPES, self::POST_TYPE_MAXIMUM);

        if (\post_type_exists($definition->key)) {
            throw ContentTypeCollision::alreadyRegistered('post type', $definition->key);
        }

        $args = $this->filteredArgs(Hooks::POST_TYPE_ARGS, $definition->args, $definition->key, $definition);

        $registered = \register_post_type($definition->key, $args);

        if ($registered instanceof \WP_Error) {
            throw InvalidContentTypeKey::refusedByCore('post type', $definition->key, (string) $registered->get_error_message());
        }
    }

    public function registerTaxonomy(Taxonomy $definition): void
    {
        $this->assertKey('taxonomy', $definition->key, self::RESERVED_TAXONOMIES, self::TAXONOMY_MAXIMUM);

        if (\taxonomy_exists($definition->key)) {
            throw ContentTypeCollision::alreadyRegistered('taxonomy', $definition->key);
        }

        $args = $this->filteredArgs(Hooks::TAXONOMY_ARGS, $definition->args, $definition->key, $definition);

        $registered = \register_taxonomy($definition->key, $definition->objectTypes, $args);

        if ($registered instanceof \WP_Error) {
            throw InvalidContentTypeKey::refusedByCore('taxonomy', $definition->key, (string) $registered->get_error_message());
        }
    }

    public function registerRestRoute(RestRoute $definition): void
    {
        if (1 !== preg_match('/^[a-z][a-z0-9_-]*\\/v[0-9]+$/', $definition->namespace)) {
            throw InvalidRestRoute::badNamespace($definition->namespace);
        }

        $this->assertSegments($definition->route);

        $args = $this->filteredRouteArgs($definition);

        if (array_key_exists('permission_callback', $args)) {
            throw InvalidRestRoute::permissionCallbackRedeclared($definition->route);
        }

        \add_action(
            Hooks::REST_API_INIT,
            static function () use ($definition, $args): void {
                \register_rest_route($definition->namespace, $definition->route, [
                    ...$args,
                    'permission_callback' => static fn (\WP_REST_Request $request): bool|\WP_Error => $definition->permission->allowed($request) ? true : RestError::forbidden(),
                ]);
            },
            priority: 10,
            accepted_args: 0,
        );
    }

    public function payload(array $payload, object $object, RestRoute $route): array
    {
        $filtered = \apply_filters(Hooks::REST_PAYLOAD, $payload, $object, $route);

        if (!is_array($filtered)) {
            throw InvalidFilterResult::notAnArray(Hooks::REST_PAYLOAD);
        }

        return $filtered;
    }

    /**
     * The key rules, in the order a consumer meets them: the reserved list,
     * the naming convention, the length cap. A reserved name is refused first
     * because core owns it whatever its shape, and the report must name the
     * reservation. The collision check follows in the caller, against core's
     * own registry.
     *
     * @param list<string> $reserved
     */
    private function assertKey(string $kind, string $key, array $reserved, int $maximum): void
    {
        if (in_array($key, $reserved, true)) {
            throw ReservedContentTypeName::forKey($kind, $key);
        }

        if (1 !== preg_match(self::KEY_PATTERN, $key)) {
            throw InvalidContentTypeKey::notNamespaced($kind, $key);
        }

        if (strlen($key) > $maximum) {
            throw InvalidContentTypeKey::tooLong($kind, $key, $maximum);
        }
    }

    /**
     * A registration argument array is a name-to-value map; a filter that
     * returns a list would hand core positional keys, so the key shape is
     * checked as loudly as the array shape.
     *
     * @param array<string, mixed> $args
     *
     * @return array<string, mixed>
     */
    private function filteredArgs(string $hook, array $args, string $key, PostType|Taxonomy $definition): array
    {
        $filtered = \apply_filters($hook, $args, $key, $definition);

        if (!is_array($filtered)) {
            throw InvalidFilterResult::notAnArray($hook);
        }

        $arguments = [];

        foreach ($filtered as $name => $value) {
            if (!is_string($name)) {
                throw InvalidFilterResult::notAnArgumentMap($hook);
            }

            $arguments[$name] = $value;
        }

        return $arguments;
    }

    /**
     * A REST route's arguments carry the same shape rule: a map of names to
     * values, never a list.
     *
     * @return array<string, mixed>
     */
    private function filteredRouteArgs(RestRoute $definition): array
    {
        $filtered = \apply_filters(Hooks::REST_ROUTE_ARGS, $definition->args, $definition);

        if (!is_array($filtered)) {
            throw InvalidFilterResult::notAnArray(Hooks::REST_ROUTE_ARGS);
        }

        $arguments = [];

        foreach ($filtered as $name => $value) {
            if (!is_string($name)) {
                throw InvalidFilterResult::notAnArgumentMap(Hooks::REST_ROUTE_ARGS);
            }

            $arguments[$name] = $value;
        }

        return $arguments;
    }

    /**
     * A route's segments are lowercase kebab-case, without a leading, trailing
     * or doubled slash. A captured placeholder -- core's (?P<id>[\d]+) form --
     * is part of the route grammar and is accepted as a segment.
     */
    private function assertSegments(string $route): void
    {
        if ('' === $route
            || str_starts_with($route, '/')
            || str_ends_with($route, '/')
            || str_contains($route, '//')
        ) {
            throw InvalidRestRoute::badSegment($route);
        }

        foreach (explode('/', $route) as $segment) {
            if (str_contains($segment, '(')) {
                continue;
            }

            if (1 !== preg_match('/^[a-z0-9]+(-[a-z0-9]+)*$/', $segment)) {
                throw InvalidRestRoute::badSegment($route);
            }
        }
    }
}
