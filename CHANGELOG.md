# Changelog

All notable changes to this package are recorded here, in Keep a Changelog
order. The format follows Semantic Versioning; a major entry names each removal.

## [Unreleased]

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
