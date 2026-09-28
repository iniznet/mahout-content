# Changelog

All notable changes to this package are recorded here, in Keep a Changelog
order. The format follows Semantic Versioning; a major entry names each removal.

## [Unreleased]

### Changed

- The generated hook reference is now two documents — `docs/reference/actions.md` and
  `docs/reference/filters.md`, replacing `docs/reference/hooks.md`. A single mixed table
  asked the reader to filter rows for the question they actually came with, which hooks
  fire and forget versus which hooks return a value, and that distinction is already
  recorded on every constant's docblock. `composer hooks:check` gates both files, and a
  package that declares none of one kind still carries the other document, so the gate
  cannot quietly stop running. Adopted from `iniznet/mahout-devtools` 2.0.1, whose
  `hooks:check` and `hooks:generate` take `--outdir=docs/reference`; the canonical command
  text lives in that package's gate manifest, and this repository's scripts are compared
  against it by `composer config:check`.

### Added

- The declarative definitions: `PostType`, `Taxonomy` and `RestRoute` —
  final readonly value objects handed to one registrar.
- The public `Contracts` surface: `Registrar`, the content-model registration
  surface, and `Permission`, the authorisation seam for a REST route.
- `ContentProvider`, the composition-root entry point: it declares the
  WordPress-backed registrar under the `Registrar` contract and attaches no
  hook of its own.
- The registration refusals, each an exception with named constructors and
  typed context: `ReservedContentTypeName`, `ContentTypeCollision`,
  `InvalidContentTypeKey`, `InvalidRestRoute` and `InvalidFilterResult`,
  behind the `MahoutException` marker interface.
- The REST side: `PublicAccess` as the explicit public declaration,
  `RestError` as the package's one error shape, and `RestErrorCode` as the
  stable machine codes a client may branch on.
- `RewriteSlugs` and `RewriteCollision`: the rewrite slugs a declared content
  model claims, and the collisions among them, computed over definitions only.
- The four `mahout/content/*` filters — `post_type_args`, `taxonomy_args`,
  `rest_route_args` and `rest_payload` — declared on the `Hooks` class and
  emitted with the documented arguments.
- The architecture-rule proof fixtures for the explicit REST
  `permission_callback` rule.
- `bin/doctor-rewrite.php`: the content-model doctor check for rewrite slug
  collisions, composed through the devtools `Check` seam.
