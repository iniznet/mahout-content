# Decisions

The package-scoped decisions of mahout-content: the choices this repository made
that its own discipline contract does not settle, each with its context, the
decision and its consequences. The discipline contract is
[AGENTS.md](../../AGENTS.md); a deviation it records points here instead of
editing the contract.

| ADR | Decision | Status |
|---|---|---|
| [0001](./0001-definition-validation-lives-at-registration.md) | Definitions validate nothing at construction; every rule runs at registration | accepted |
| [0002](./0002-rest-registration-is-deferred-to-rest-api-init.md) | Post types and taxonomies register immediately; a REST route's core call is deferred to `rest_api_init` | accepted |
| [0003](./0003-rewrite-collision-check-is-composed-by-a-dev-script.md) | The rewrite-collision check is a domain class composed into the doctor by a dev-only script | accepted |
| [0004](./0004-the-committed-lockfile-is-resolved-through-the-uncommitted-path-repositories.md) | The committed lockfile is the dev manifest's resolution | accepted |
