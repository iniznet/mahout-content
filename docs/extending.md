# Extending

This package accepts extension through four surfaces: the four content filters,
the `Contracts\\Permission` seam, the `Registrar::payload()` path, and the
content-model doctor check. There is no event system beyond these, no
service locator and no other hook.

## The hooks this package emits

Declared on the `Hooks` class; the generated reference is
`docs/reference/hooks.md`. Names are `mahout/{package}/{event}` and never
appear inline.

| Hook | Type | Fires with | When |
|---|---|---|---|
| `mahout/content/post_type_args` | filter | `array $args`, `string $postType`, `PostType $definition` | before `register_post_type()` |
| `mahout/content/taxonomy_args` | filter | `array $args`, `string $taxonomy`, `Taxonomy $definition` | before `register_taxonomy()` |
| `mahout/content/rest_route_args` | filter | `array $args`, `RestRoute $route` | before the route registers on `rest_api_init` |
| `mahout/content/rest_payload` | filter | `array $payload`, `object $object`, `RestRoute $route` | when the consumer's mapper serves a resource |

The package observes one core hook, `rest_api_init`, which is also a constant
on the `Hooks` class: it is the only hook the registrar attaches to, because
`register_rest_route()` must run on or after it.

The filters pass values and the package's own readonly definition objects,
never a mutable WordPress object. A filter that returns anything but an array
throws `InvalidFilterResult` naming the hook, and a filter that returns a list
where a name-to-value map is required is refused too — core would receive
positional keys and every named argument would be lost.

## Worked example: tailoring registration through a filter

```php
use Iniznet\Mahout\Content\Hooks;

add_filter(Hooks::POST_TYPE_ARGS, function (array $args, string $postType, $definition): array {
    if ('howdah_series' === $postType) {
        $args['menu_icon'] = 'dashicons-playlist-video';
    }

    return $args;
}, 10, 3);
```

The filter fires once per registration, with the declared args, the key and the
definition object. Returning the args unchanged is a valid answer; returning
anything but an argument map stops the registration loudly.

## Worked example: a route permission

A route cannot be registered without a permission. Implement the interface and
hand the instance to the route:

```php
use Iniznet\Mahout\Content\Contracts\Permission;

final class EditPostsPermission implements \Iniznet\Mahout\Content\Contracts\Permission
{
    public function allowed(\WP_REST_Request $request): bool
    {
        return current_user_can('edit_posts');
    }
}

$registrar->registerRestRoute(new RestRoute(
    namespace: 'howdah/v1',
    route: 'series',
    args: ['methods' => 'GET', 'callback' => $callback],
    permission: new EditPostsPermission(),
));
```

A denial is wrapped by the registrar: the response carries
`mahout_content_forbidden`, a translated message and
`rest_authorization_required_code()` — 401 for an anonymous requester, 403
otherwise. A route never declares `permission_callback` in its args; the
definition's `Permission` is the one declaration, and a redeclaration is
refused with `InvalidRestRoute::permissionCallbackRedeclared()`.
`PublicAccess` is the explicit declaration of a genuinely public route — a
public route still declares a permission.

## Worked example: reshaping a REST payload

The payload is the resource a consumer's mapper produced — a name-to-value map
for one resource, or a list for a collection. `Registrar::payload()` applies
the filter before the response leaves the mapper:

```php
use Iniznet\Mahout\Content\Contracts\Registrar;
use Iniznet\Mahout\Content\Hooks;

$registrar->payload(['id' => 5, 'title' => 'Name'], $post, $route);

add_filter(Hooks::REST_PAYLOAD, function (array $payload, object $object, $route): array {
    return [...$payload, 'links' => ['self' => rest_url($route->namespace . '/' . $route->route)]];
}, 10, 3);
```

A filter may reshape the payload but must return an array of either shape; a
non-array result throws `InvalidFilterResult` naming the hook.

## Worked example: the content-model doctor

`RewriteSlugs` computes, over the declared definitions, every rewrite slug
claimed by two or more of them — a collision core resolves silently, by
registration order. The computation is a pure domain class, and
`bin/doctor-rewrite.php` composes it into the devtools doctor:

```bash
# A file returning the declared definitions, as a list of PostType|Taxonomy:
php bin/doctor-rewrite.php fixtures/content-model/rewrite-collision.php   # exits 1 on a collision
php bin/doctor-rewrite.php fixtures/content-model/clean.php               # exits 0
```

A content-model file returns a list of `PostType` and `Taxonomy` definitions.
With no file named, the check passes on the statement that no content model is
declared. The collision report names the slug and every declaration claiming
it, for example:

```
FAIL Rewrite slug collision "series" claimed by post type fixture_series, taxonomy fixture_genre
```

To add a check of your own, implement the devtools `Contracts\\Check` interface
and pass the instance to the doctor, as `bin/doctor-rewrite.php` does — the
doctor itself never grows a branch.

## What this package does not accept

- A registration at file scope, or a hook emitted from anywhere but the
  registrar's one path.
- A second permission declaration beside `Contracts\\Permission`.
- A raw hook name: every name is a `Hooks` constant.
- A filter result that is not an argument map.
