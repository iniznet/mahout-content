# ADR-0004 — The committed lockfile is resolved through the uncommitted path repositories

Status: accepted

## Context

REP-11 forbids a `path` repository in a committed `composer.json`, and the
divergence gate enforces it. Neither `mahout-kernel` (a `require`) nor
`iniznet/mahout-devtools` (a `require-dev`) is published yet, so the committed
manifest cannot resolve its dependencies from a published lane, yet the
divergence gate requires the committed `composer.lock` to pin the analyzer
configuration.

This package consumes two siblings, where mahout-kernel consumed one.

## Decision

Local development resolves both sibling checkouts:

- `composer.dev.json` (git-ignored) adds a `path` repository for each of
  `../mahout-kernel` and `../mahout-devtools` with `symlink: true`, and
  requires both at `@dev`.
- The package's `extra.branch-alias` maps `dev-main` to `1.0.x-dev`, so `@dev`
  and the committed `^1.0` name the same major.
- `composer.dev.lock` is generated from that manifest and is git-ignored.
- The committed `composer.lock` is the same resolution — the same
  `content-hash`, both `iniznet/*` packages pinned — so the divergence gate
  can prove the pinned configuration.

## Consequences

- The committed lock names path distributions for both siblings until they are
  published. At publication the lock is regenerated from the VCS lane and this
  ADR is superseded, exactly as mahout-kernel's ADR-0005 states for its own
  lockfile.
- A contributor in a clean clone runs `COMPOSER=composer.dev.json composer
  install`; the committed manifest alone does not resolve until publication.
