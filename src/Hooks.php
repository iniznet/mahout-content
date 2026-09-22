<?php

declare(strict_types=1);

namespace Iniznet\Mahout\Content;

/**
 * Every hook mahout-content emits or observes. Names are declared once, here.
 *
 * The core hook is a constant too: the rule that bans a raw hook name at an
 * emit site applies to a core hook as much as to a mahout one, and rest_api_init
 * is the only hook this package attaches to.
 */
final class Hooks
{
    /**
     * Filters the arguments a post type is registered with.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param array<string, mixed> $args       the registration arguments, as declared
     * @param string               $postType   the post type key
     * @param PostType             $definition the declarative definition
     */
    public const string POST_TYPE_ARGS = 'mahout/content/post_type_args';

    /**
     * Filters the arguments a taxonomy is registered with.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param array<string, mixed> $args       the registration arguments, as declared
     * @param string               $taxonomy   the taxonomy key
     * @param Taxonomy             $definition the declarative definition
     */
    public const string TAXONOMY_ARGS = 'mahout/content/taxonomy_args';

    /**
     * Filters the arguments a REST route is registered with.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param array<string, mixed> $args  the route arguments, as declared
     * @param RestRoute            $route the declarative route definition
     */
    public const string REST_ROUTE_ARGS = 'mahout/content/rest_route_args';

    /**
     * Filters a REST resource's payload before it is served.
     *
     * @since 1.0
     *
     * @filter
     *
     * @param array<string, mixed> $payload the mapped resource
     * @param object               $object  the object the payload was mapped from
     * @param RestRoute            $route   the route serving it
     */
    public const string REST_PAYLOAD = 'mahout/content/rest_payload';

    /**
     * Core's REST bootstrap action: the only hook the registrar attaches to,
     * because register_rest_route() must run on or after rest_api_init.
     *
     * @since 1.0
     *
     * @action
     *
     * @param \WP_REST_Server $server the REST server being initialised
     */
    public const string REST_API_INIT = 'rest_api_init';
}
