# ADR-0001 — Definition validation lives at registration, not at construction

Status: accepted

## Context

The design sketch validated a definition at construction: a `PostType` with a
mixed-case key or an over-length name would be unconstructable, and law 3
("fail fast and loud") would be satisfied at declaration time, before any hook
fires.

Two facts pull the other way. First, the rewrite-collision check and the
content-model doctor consume definitions as data — a doctor fixture or a
model file must be able to hold a definition whose registration would fail,
or every static analysis of a content model would have to be valid WordPress
state. Second, the arguments a filter may reshape and the key rules are
checked against different authorities: the args belong to core's own shapes,
while the key rules exist because core's behaviour on the same input is a
silent, order-dependent overwrite.

## Decision

The definition classes (`PostType`, `Taxonomy`, `RestRoute`) are data: final
readonly value objects with no validation logic. Every rule — the naming
convention, the length caps, the reserved lists, the collision check, the REST
namespace and segment grammar, and the `permission_callback` redeclaration
check — runs in one place, `Internal\\WordPressRegistrar`, at registration
time, before core is called. A registration refusal is a loud exception with
typed context getters (`kind()`, `key()`, `value()`, `hook()`).

## Consequences

- A definition can exist without being registrable. A consumer that declares
  an invalid definition learns about it at registration, with a message naming
  the condition, rather than at construction.
- There is exactly one validation path. A future second construction site
  (a scaffold generator, a consumer importer) inherits the rules by calling
  the registrar, not by copying checks.
- The fail-fast boundary is the registration call, not the constructor. The
  tests prove every refusal at the registrar, against core's own registry.
