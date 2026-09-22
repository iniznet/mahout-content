# Getting started

## Install

The package is consumed over VCS; there is no Packagist lane.

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

The package requires PHP 8.4 and WordPress 7.1 or later, and depends on
`iniznet/mahout-kernel`. In a development checkout of this repository, the
sibling packages are resolved through the uncommitted `composer.dev.json`
(a `path` repository plus `@dev`):

```bash
COMPOSER=composer.dev.json composer install
```

## Configure

Nothing is configured and nothing is registered at file scope. The package
ships one provider, `ContentProvider`, which declares a single service — the
WordPress-backed registrar — under the `Contracts\\Registrar` interface a
consumer depends on. It attaches no hook of its own.

## First working use

Declare definitions in a module and hand them to the registrar during boot:

```php
<?php

declare(strict_types=1);

namespace App\Modules\Series;

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
    }

    public function boot(Container $container): void
    {
        $registrar = $container->get(\Iniznet\Mahout\Content\Contracts\Registrar::class);

        $registrar->registerPostType(new PostType(
            key: 'howdah_series',
            args: [
                'labels' => ['name' => 'Series', 'singular_name' => 'Series'],
                'public' => true,
                'has_archive' => true,
                'show_in_rest' => true,
                'supports' => ['title', 'editor'],
                'rewrite' => ['slug' => 'series'],
            ],
        ));

        $registrar->registerTaxonomy(new Taxonomy(
            key: 'howdah_genre',
            objectTypes: ['howdah_series'],
            args: ['show_in_rest' => true],
        ));

        $registrar->registerRestRoute(new RestRoute(
            namespace: 'howdah/v1',
            route: 'series',
            args: ['methods' => 'GET', 'callback' => static fn (): array => []],
            permission: new PublicAccess(),
        ));
    }
}
```

Three rules the definitions must satisfy:

- The key is `<slug>_<name>` in lowercase — `howdah_series`, never `series` —
  within core's own length caps (20 characters for a post type, 32 for a
  taxonomy). A name core reserves (`post`, `category`, `wp_block`, …) is
  refused.
- The REST namespace carries the version: `howdah/v1`, never a bare slug. Route
  segments are lowercase kebab-case; a captured placeholder such as
  `(?P<id>[\d]+)` is part of the grammar and is accepted.
- A route's permission is a collaborator, not a callback string: `RestRoute`
  cannot be constructed without a `Contracts\\Permission`, and
  `PublicAccess` is the explicit declaration of a genuinely public route.

Registering during kernel boot is safe for post types and taxonomies: the
kernel boots before `init`, so `register_post_type()` and
`register_taxonomy()` run immediately at the call site. The REST call is
deferred to `rest_api_init` for you, because core refuses
`register_rest_route()` any earlier.

## The failure modes a newcomer hits

| Symptom | Cause |
|---|---|
| `InvalidContentTypeKey: must match <slug>_<name>` | the key has no underscore, mixed case, or exceeds the length cap. A mixed-case key is refused because core would silently lowercase it into a different key |
| `ReservedContentTypeName` | the key is one of WordPress's own post types or taxonomies; the check runs before the collision check so the report names the reservation |
| `ContentTypeCollision` | the key is already registered. Core would silently overwrite; this package refuses, because whichever registration won is a config order accident |
| `InvalidRestRoute` on a namespace | the namespace must be `<slug>/v<n>` in lowercase; the version lives in the path |
| `InvalidRestRoute` on a route | segments must be lowercase kebab-case, without a leading, trailing or doubled slash |
| `InvalidRestRoute::permissionCallbackRedeclared` | the route's args carry `permission_callback`. The `Permission` collaborator is the one declaration; the registrar injects the key |
| `InvalidFilterResult` naming a `mahout/content/*` filter | a filter returned something other than an argument array, or a list where a name-to-value map is required |
| `composer doctor` reports a rewrite slug collision | two declared definitions claim the same slug. Run `php bin/doctor-rewrite.php <content-model.php>` with a file returning the declared definitions to see every claim |
