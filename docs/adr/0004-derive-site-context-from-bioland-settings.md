---
status: accepted
date: 2026-06-23
deciders: [scbd-bl2-team]
context: system-wide
code-path: src/Plugin/Field/FieldWidget/, src/Utility/BiosafetyContext.php
origin: standalone
---

# 0004. Derive site context from bioland.settings

The Widget reads the host site's country list and biosafety flag directly from the `bioland.settings`
configuration (`countries`, `is_biosafety_land`) rather than defining its own. These values feed the
Biosafety context and Auto-add. We chose to reuse Bioland's existing per-site configuration so that
country and biosafety status are set once for the site and not duplicated in this module. The cost is
a runtime coupling to the Bioland distribution: on a site without `bioland.settings` the Widget falls
back to non-biosafety defaults.

## Consequences
- The module still installs and works outside Bioland, but silently loses Auto-add and the biosafety
  default Domain order there.
- A change to Bioland's config keys would break this behaviour with no compile-time signal.
