# Security policy

## The private route

Report a vulnerability through GitHub's private vulnerability reporting on this
repository (the *Security* tab, then *Report a vulnerability*). No security email
address is published.

## Response expectation

| Stage | Commitment |
|---|---|
| Acknowledgement | within 3 working days |
| Initial assessment | within 10 working days |
| Fix or a written plan | a defect in a repository-owned surface gets a fix or a dated plan |
| Disclosure | coordinated with the reporter, after the fix or after 90 days, whichever comes first |
| Credit | offered, never assumed |

## What is in scope in this repository

- The `Contracts\\Registrar` surface and the refusals it performs before
  WordPress sees a definition: reserved names, the naming convention, the
  length caps, and collisions.
- The `Contracts\\Permission` seam and the injected REST `permission_callback`,
  including the one denial shape every route shares.
- The `RestError` error shape and the stable `RestErrorCode` values.
- The four `mahout/content/*` filters and their argument contracts.

A defect in a different repository of the family belongs in that repository.
The theme's cross-cutting surfaces — the request adapter, the field REST route,
the transaction boundary, escaping on output and the cacheability policy — are
stated in the theme repository's security policy; report a defect there when it
belongs to one of those surfaces.
