# Contracts

`src/Contracts/` is the package's entire public API and the surface semantic
versioning governs: a change to an interface here is a contract change and is
published as a major. Everything under `src/Internal/` is `@internal` and may
change in a patch release.

## Interfaces

### `Contracts\\Permission`

The authorisation seam for a REST route.

```php
interface Permission
{
    public function allowed(\WP_REST_Request $request): bool;
}
```

| | |
|---|---|
| Role | decides only whether a request is allowed |
| Implementations in this package | `PublicAccess` — the explicit declaration of a genuinely public route |
| Implementations expected of a consumer | a capability check, a nonce check, or any other request-time authorisation |
| What the registrar does with it | wraps the answer: `true` is passed through, a denial becomes the package's one error shape (`RestError::forbidden()`), so every denied request has one error shape rather than one per route |

A route cannot be constructed without a `Permission`. A public route still
declares one — `PublicAccess` is the declaration, not the absence of one.

### `Contracts\\Registrar`

The content-model registration surface. A consumer depends on this interface
and receives the WordPress-backed implementation from the container, so the
registration call sites name the contract rather than a concrete class.

```php
interface Registrar
{
    public function registerPostType(PostType $definition): void;
    public function registerTaxonomy(Taxonomy $definition): void;
    public function registerRestRoute(RestRoute $definition): void;
    public function payload(array $payload, object $object, RestRoute $route): array;
}
```

| | |
|---|---|
| Role | registers post types, taxonomies and REST routes from declarative definitions, and applies the REST payload filter |
| Implementations in this package | `Internal\\WordPressRegistrar`, declared by `ContentProvider` under this interface |
| Registration timing | post types and taxonomies register immediately at the call site; a REST route's core call is deferred to `rest_api_init` |
| Refusals | `ReservedContentTypeName`, `InvalidContentTypeKey`, `ContentTypeCollision`, `InvalidRestRoute`, `InvalidFilterResult` — each thrown before WordPress sees the definition, or naming a core refusal the package's own checks could not see coming |

## What a consumer may rely on

| Surface | Guarantee |
|---|---|
| `Contracts\\Permission` and `Contracts\\Registrar` | stable within a major version |
| The definition classes `PostType`, `Taxonomy`, `RestRoute` — constructor signatures and public properties | stable within a major; they are the documented public concrete classes |
| `PublicAccess`, `RestError`, `RestErrorCode`, `RewriteSlugs`, `RewriteCollision`, `Hooks`, `ContentProvider` | stable within a major; documented public concrete classes |
| The four `mahout/content/*` hook names and their argument order | stable within a major; declared on the `Hooks` class, never inline |
| The `RestErrorCode` enum values | stable machine codes: a client branches on them, and they never change meaning within a major |
| `src/Internal/` | no guarantee. The WordPress-backed registrar may change in a patch release; resolve the contract, never the implementation |

## Documented public concrete classes

| Class | Role |
|---|---|
| `PostType` | a declarative post type: `key` plus the `register_post_type()` args, exactly as core declares them |
| `Taxonomy` | a declarative taxonomy: `key`, the post types it is attached to, and the args |
| `RestRoute` | a declarative REST route: versioned namespace, kebab-case route, args, and its `Permission` |
| `PublicAccess` | the explicit public permission |
| `RestError` | the package's one REST error shape: stable code, translated message, valid HTTP status |
| `RestErrorCode` | the stable machine codes (`NotFound`, `InvalidParameter`, `Forbidden`) |
| `RewriteSlugs` | the collisions among the rewrite slugs a declared content model claims |
| `RewriteCollision` | one collision: the slug and every declaration claiming it |
| `Hooks` | every hook constant the package emits or observes |
| `ContentProvider` | the kernel `ServiceProvider` that declares the registrar under the contract |
