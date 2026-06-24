---
status: accepted
date: 2026-06-23
deciders: [scbd-bl2-team]
context: system-wide
code-path: src/Plugin/Field/FieldType/
origin: standalone
---

# 0002. Persist stable Term keys, not Labels

The SCBD Thesaurus field stores the canonical Term key for each tag (for example `GBF-TARGET-03`,
`SUSTAINABLE-DEVELOPMENT-GOAL-06`, or a national-target UUID), never the human-readable Label.
Labels are resolved for display at render time from the SCBD thesaurus. We chose keys so that saved
tags stay valid when Labels are translated, renamed, or reordered, and so cross-site reporting and
GBF/SDG enrichment keep matching on one durable identifier. The cost is that stored values are
opaque without a thesaurus lookup, and a label resolution layer is always required.

## Consequences
- Changing the stored representation later requires migrating saved field data across every site.
- Legacy keys are normalised on save (for example `SDG-GOAL-*` is rewritten to
  `SUSTAINABLE-DEVELOPMENT-GOAL-*`) so the stored vocabulary converges over time.
