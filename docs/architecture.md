# Architecture

mahout-content is a registration layer. It owns no storage, renders nothing and
owns no request boundary; it owns the moment a declared content model becomes a
WordPress registration, and it makes every refusal in that moment loud.

## The one path

```
consumer Module::boot( Container )
  \-> $container->get( Contracts\Registrar::class )   # by contract, never the implementation
        ->registerPostType( PostType )      # immediate: register_post_type()
        ->registerTaxonomy( Taxonomy )      # immediate: register_taxonomy()
        ->registerRestRoute( RestRoute )    # deferred: register_rest_route() on rest_api_init
  ->payload( payload, object, route )       # the one REST payload filter, applied by the consumer's mapper
```

`ContentProvider` is the composition-root entry point. Its `register()` declares
one service — `Internal\\WordPressRegistrar` — under the `Contracts\\Registrar`
interface, so a consumer resolves the contract and never names an Internal
class. Its `boot()` attaches nothing: every registration is an explicit call
from a consumer's module, and nothing is registered at file scope.

## Layers and dependency direction

```
ContentProvider (composition root)
      |
      v
Contracts\Registrar  <---- consumer modules depend on this interface
      ^
      |
Internal\WordPressRegistrar   -- the only class that calls WordPress
      |
      v
PostType / Taxonomy / RestRoute  (definitions) + Hooks (the four filters)
      |
      v
WordPress: register_post_type(), register_taxonomy(), register_rest_route()
```

Arrows point one way. The definitions never call WordPress; `RewriteSlugs`
computes collisions over definitions only, which is what makes it provable in a
unit test; the registrar is the only file that touches a registration function;
and the provider never contains a rule. Nothing in this package renders, reads
a superglobal, touches storage or caches.

## The single validation point

The naming convention, the length caps, the reserved lists and the collision
check all run in one place — the registrar — before core is called. The reason
is core's own behaviour on the same input:

| Input | What core does | What this package does |
|---|---|---|
| A mixed-case key | `sanitize_key()` lowercases it silently | refused with `InvalidContentTypeKey` before the core call |
| A key already registered | the old registration is silently overwritten | refused with `ContentTypeCollision` |
| A reserved name | registered, shadowing core's own type or taxonomy | refused with `ReservedContentTypeName`, before the collision check, so the report names the reservation and not an order-dependent accident |
| A key core itself rejects | returns a `WP_Error` the caller often ignores | surfaced as `InvalidContentTypeKey::refusedByCore()` with core's message |

A registration is therefore order-independent: the refusal names its condition
(`kind()`, `key()`, `value()`, `hook()`) instead of leaving the caller to infer
it from an overwrite.

## Timing: immediate, except where core polices it

The kernel boots during `setup_theme`, before `init`. At that point core's
built-in post types and taxonomies exist and `WP_Rewrite` already exists, so
post types and taxonomies register immediately at the call site and the
declared rewrite slugs are safe. `register_rest_route()` is the one function
core polices by time — since 5.1.0 it raises `_doing_it_wrong()` when called
before `rest_api_init` — so a route's core call is deferred through a handler
attached to `rest_api_init`. One path per kind. The package attaches exactly
one core hook, and it is a constant on the `Hooks` class like every other name.

## The permission seam

A REST route's `Permission` is the one permission declaration. The registrar
injects the explicit `permission_callback` key core requires — an architecture
rule refuses a bare `register_rest_route()` without one, and the registrar
refuses a definition that redeclares the key in its args, because two ways to
declare one permission is one way too many. The injected callback returns
`true` when the permission allows the request and `RestError::forbidden()`
otherwise, so every denial carries the same stable code and core's
`rest_authorization_required_code()` status — 401 anonymous, 403 logged in.

## Error taxonomy

Every thrown condition extends the most specific SPL exception and implements
the `MahoutException` marker, so `catch (MahoutException)` catches everything
this package throws and nothing else. Every REST error is `RestError`: a stable
machine code from `RestErrorCode`, a translated message free of exception text
and paths, and a valid HTTP status in the error data.

## Seams

| Seam | How it is extended |
|---|---|
| Registration args | the four `mahout/content/*` filters; a filter reshapes the declared args before core sees them and must return an argument map |
| REST authorisation | implement `Contracts\\Permission` and hand the instance to the route |
| REST payload | the consumer's mapper produces the payload array; `Registrar::payload()` applies the `rest_payload` filter and refuses a non-array result |
| Doctor checks | `RewriteSlugs` is a pure domain class; `bin/doctor-rewrite.php` composes it into the devtools doctor through the `Check` interface — the doctor itself never grows a branch |
