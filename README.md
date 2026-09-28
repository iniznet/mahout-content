# mahout-content

## What it is

Declarative post type, taxonomy, rewrite and REST registration for the mahout
family: a consumer declares definitions, hands them to one registrar, and the
package validates the key, refuses reserved names and collisions, applies the
documented filters and calls WordPress's registration functions. It renders
nothing and owns no storage.

## Installation

There is no Packagist lane. Consume the repository over VCS and pin the major:

```json
{
    "repositories": [
        { "type": "vcs", "url": "https://github.com/iniznet/mahout-content.git" }
    ],
    "require": {
        "iniznet/mahout-content": "^1.0"
    }
}
```

```bash
composer require iniznet/mahout-content:^1.0
```

A development checkout points at sibling directories through an uncommitted
`composer.dev.json` (a `path` repository plus `@dev`) and runs
`COMPOSER=composer.dev.json composer install`.

## The public `Contracts/` surface

`src/Contracts/` is the package's entire public API. Everything under
`src/Internal/` is `@internal` and may change in a patch release.

| Interface | Role | Implementations |
|---|---|---|
| `Registrar` | the content-model registration surface: post types, taxonomies, REST routes and the REST payload filter | `Internal\\WordPressRegistrar` |
| `Permission` | the authorisation seam for a REST route | `PublicAccess`; consumer implementations |

## Minimal usage

A consumer's module resolves the `Registrar` by contract and hands it
definitions. Post types and taxonomies register immediately at the call site;
the REST call is deferred to `rest_api_init`, which core requires.

```php
<?php

declare(strict_types=1);

use Iniznet\Mahout\Content\ContentProvider;
use Iniznet\Mahout\Content\Contracts\Registrar;
use Iniznet\Mahout\Content\PostType;
use Iniznet\Mahout\Content\PublicAccess;
use Iniznet\Mahout\Content\RestRoute;
use Iniznet\Mahout\Content\Taxonomy;
use Iniznet\Mahout\Kernel\Container;

final class SeriesModule implements \Iniznet\Mahout\Kernel\Contracts\Module
{
    public function register(Container $container): void
    {
        $container->provider(ContentProvider::class);
    }

    public function boot(Container $container): void
    {
        $registrar = $container->get(Registrar::class);

        $registrar->registerPostType(new PostType(
            key: 'howdah_series',
            args: ['public' => true, 'has_archive' => true, 'show_in_rest' => true],
        ));

        $registrar->registerTaxonomy(new Taxonomy(
            key: 'howdah_genre',
            objectTypes: ['howdah_series'],
            args: ['show_in_rest' => true],
        ));

        $registrar->registerRestRoute(new RestRoute(
            namespace: 'howdah/v1',
            route: 'series',
            args: ['methods' => 'GET', 'callback' => static fn (): array => ['ok' => true]],
            permission: new PublicAccess(),
        ));
    }
}
```

Every route declares a permission: `RestRoute` cannot be constructed without
one, and the registrar injects the explicit `permission_callback` key core
requires. A denied request returns the package's one error shape — a stable
`RestErrorCode` value, a translated message and a valid HTTP status — never an
exception message. A route whose args also carry `permission_callback` is
refused: the `Permission` is the one declaration.

Key rules run before core sees anything, because core's own behaviour on the
same input is a silent, order-dependent overwrite: a mixed-case key would be
lowercased by `sanitize_key()` and a colliding key silently overwritten. A key
must match `<slug>_<name>` in lowercase, carry at least one underscore, and
respect core's own length caps (20 for a post type, 32 for a taxonomy); a
reserved name or an existing registration throws.

## Documented public concrete classes

Every documented public class is part of the stable surface within a major.

| Class | Role |
|---|---|
| `PostType` | a declarative post type: `key` and the `register_post_type()` args |
| `Taxonomy` | a declarative taxonomy: `key`, the post types it is attached to, and the args |
| `RestRoute` | a declarative REST route: versioned namespace, kebab-case route, args, and its `Permission` |
| `PublicAccess` | the permission for a genuinely public route — the declaration, not the absence of one |
| `RestError` | the package's one REST error shape, as named constructors returning `WP_Error` |
| `RestErrorCode` | the stable machine codes of those errors |
| `RewriteSlugs` | the slugs a declared content model claims, and the collisions among them |
| `RewriteCollision` | one slug claimed by more than one definition, with every claimant |
| `Hooks` | every hook constant the package emits or observes |
| `ContentProvider` | the composition-root entry point implementing the kernel's `ServiceProvider` |

The package emits four filters — `mahout/content/post_type_args`,
`mahout/content/taxonomy_args`, `mahout/content/rest_route_args` and
`mahout/content/rest_payload` — and attaches one core hook, `rest_api_init`.
The generated references are `docs/reference/actions.md` and `docs/reference/filters.md`; the hooks and their
argument contracts are documented in `docs/extending.md`.

## Compatibility

| Item | Value |
|---|---|
| PHP | 8.4 or later |
| WordPress | 7.1 or later |
| `Contracts/` | stable within a major version; a change is a contract change and is published as a major |
| `Internal/` | unguaranteed; may change in a patch release |
| Licence | GPL-2.0-or-later |

## Architecture

The package is one registration path. A consumer's module calls the
`Contracts\\Registrar` resolved from the kernel container; the WordPress-backed
implementation validates the definition, fires the one filter for its kind, and
calls WordPress's registration function. Post types and taxonomies register
immediately — the kernel boots before `init`, and `WP_Rewrite` already exists —
while `register_rest_route()` is the one call core polices by time, so it
registers through a handler attached to `rest_api_init`. Every refusal is a
loud exception naming the condition; nothing falls back, nothing is silently
overwritten, and `RestRoute` cannot exist without a `Permission`.

The canonical planning corpus is private and is not published with this
repository; the decisions this package made are recorded under
`docs/decisions/` and the discipline contract is `AGENTS.md`. The architecture
document is `docs/architecture.md`.

## Licence

GPL-2.0-or-later. The full text is in [LICENSE](./LICENSE).
