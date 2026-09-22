# ADR-0002 — REST route registration is deferred to rest_api_init

Status: accepted

## Context

`register_rest_route()` is the one registration function core polices by time:
since WordPress 5.1.0 it raises `_doing_it_wrong()` when called before
`rest_api_init`. Post types and taxonomies have no such gate — the kernel
boots during `setup_theme`, when `WP_Rewrite` already exists, so an immediate
`register_post_type()` from a consumer module is safe.

## Decision

`Registrar::registerPostType()` and `registerTaxonomy()` call core
immediately at the call site. `registerRestRoute()` validates the definition
and fires the `mahout/content/rest_route_args` filter at call time, then
attaches a handler to `rest_api_init` — the only hook this package observes —
which performs the core call with the injected `permission_callback`. One path
per kind; no mode switch, no environment probe.

## Consequences

- The four filters fire at registration time, not at serve time: a filter
  tailoring the args sees them once, before the route is registered.
- A route's permission callback runs at serve time, wrapped by the registrar
  so a denial has the package's one error shape.
- A consumer calling `registerRestRoute()` during boot is always correct;
  there is no "too early" failure mode to document.
