# ADR-0003 — The rewrite-collision check is a domain class composed by a dev-only script

Status: accepted

## Context

A rewrite slug collision is silent in WordPress: the second registration's
rules lose or win by registration order, and no notice fires. The corpus
assigns the collision check to the installation doctor, but the doctor ships in
mahout-devtools and builds its check list inline — a consumer check cannot be
added without the doctor growing a branch, which is exactly what the devtools
`Contracts\\Check` seam exists to prevent.

## Decision

The collision logic lives in `src/RewriteSlugs.php` — a pure domain class that
resolves no collaborator and touches no WordPress function — with
`RewriteCollision` as its finding value object. `bin/doctor-rewrite.php` is a
dev-only composition: it loads an optional content-model file (a list of
definitions), implements `Contracts\\Check` in an anonymous class, passes the
instance to the devtools doctor and exits with the report's exit code. Nothing
in mahout-devtools changes, and no source file names a devtools symbol, so a
`--no-dev` install loads nothing that is absent.

## Consequences

- The check is provable in a unit test (`RewriteSlugsTest`) with no WordPress
  function on the path.
- The doctor stays closed: a consumer extends it through the `Check` interface,
  never by editing devtools.
- `bin/` is a dev-time boundary, not analysed source; it requires the vendor
  autoloader and fails loudly when dependencies are not installed.
