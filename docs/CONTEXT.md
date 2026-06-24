# SCBD Thesaurus Tags

The ubiquitous language for the `scbd_field` Drupal module: a field and widget that tag Drupal
content with controlled-vocabulary terms published by the SCBD online thesaurus. This glossary is
the single source of naming. Use these words in code, comments, commit messages, and docs; prefer
the canonical term and avoid the listed synonyms.

This is a single bounded context. The Drupal field-storage side and the Vue picker side share one
language: Term, Term key, Domain, Label, and Group cross the Glue seam unchanged, meaning the same
thing on both sides. There is no second model and no translation seam, so there is no context map.

## Language

### Naming

**scbd_field (machine name)**:
The Drupal machine name of this module, used in every namespace, plugin id, route, config object,
library, and yml file (`scbd_field.info.yml`, `scbd_field.routing.yml`, `Drupal\scbd_field\...`).
It is **not** the same as the repository name `drupal-module-scbd-thesaurus-tags`. The repo name is
a human-facing label only; nothing in the code references it. Read every code and config token as
`scbd_field`, never as "thesaurus tags".
_Avoid_: thesaurus_tags, scbd_thesaurus_tags, drupal-module-scbd-thesaurus-tags (as a code name)

### Vocabulary

**Term**:
A single controlled-vocabulary entry that content is tagged with, such as a GBF target, an SDG, a
country, or a subject, published by the SCBD thesaurus on `api.cbd.int`.
_Avoid_: tag, keyword, taxonomy term, option

**Term key**:
The stable canonical identifier persisted for a Term, for example `GBF-TARGET-03`,
`SUSTAINABLE-DEVELOPMENT-GOAL-06`, or a national-target UUID. It is what gets saved, never the
Label, so tags survive translation and label changes. National-target keys are UUIDs; every other
Domain uses a slug.
_Avoid_: id, identifier, slug, code, value

**Label**:
The human-readable display string for a Term, resolved for the current Locale and falling back to
English, then to the raw Term key when no translation exists. A Label is shown, never stored.
_Avoid_: name, title, caption, display value

**Subject**:
A thematic-area Term. On biosafety sites the relevant variant is the BCH Subject, drawn from the
Biosafety Clearing-House vocabulary.
_Avoid_: thematic area, theme, topic

### Configuration

**Domain**:
A named category of Terms drawn from one controlled vocabulary, rendered as its own labeled
dropdown in the Widget. The known set is `gbfTargets`, `nationalTargets7`, `sdgs`, `countries`,
`subjects`, `bchSubjects`, `bchSubjectGroups`, `regions`, and `ecosystemTypes`.
_Avoid_: vocabulary, taxonomy, list, facet

**Domain order**:
The administrator-configured, ordered list of Domain IDs that decides which Domains appear and in
what sequence. Any ID outside the known Domain set is rejected.
_Avoid_: domain list, domain config, display order

**Group**:
A parent heading inside a grouped Domain (notably BCH subject groups) whose child Terms can be
selected together. A Group is a level within a Domain, not a Domain itself.
_Avoid_: domain, cluster, category, set

### Biosafety behaviour

**Biosafety context**:
The condition under which biosafety-specific behaviour applies. Canonically it is true when the
site is a biosafety (BCH) site or the field's Domains include a BCH Domain (`bchSubjects`,
`bchSubjectGroups`). It governs Auto-add and the biosafety default Domain order. Note the two
triggers do not have the same reach at runtime: the Widget's Auto-add path keys on the site
biosafety flag alone, while the two-part definition lives in the `BiosafetyContext` helper.
_Avoid_: BCH mode, biosafety mode, biosafety flag

**Auto-add**:
The automatic insertion of default Terms into a new entity's field before editing begins. In a
Biosafety context this adds GBF Target 17 and the site's single configured country, unless the
administrator disables each.
_Avoid_: prefill, auto-populate, seeding, default tags

**Auto-link**:
The one-way, additive expansion where selecting a GBF target Term pulls in its related SDG and
Subject Terms. Picking an SDG or Subject pulls in nothing, so the expansion never cascades.
_Avoid_: cross-walk, cascade, mapping, enrichment

**GBF Target 17**:
The Global Biodiversity Framework target that Auto-add inserts on biosafety sites. Named here
because the module treats it as a specific, hardcoded default Term.
_Avoid_: target 17, GBF17

### Module composition

**SCBD Thesaurus field**:
The Drupal field type this module provides (`scbd_field_thesaurus`) for tagging content with Terms.
One field can carry many Terms across several Domains.
_Avoid_: tag field, taxonomy field, term reference field

**Widget**:
The Drupal field widget that renders the SCBD Thesaurus field on a content-edit form and hosts the
picker. It owns the field's stored value and emits the element the Bundle mounts into.
_Avoid_: field, input, multiselect, picker

**Bundle**:
The prebuilt Vue 3 application that draws the picker UI (per-Domain dropdowns, search, selected
tags) inside the Widget. It reads and writes the Widget's stored value.
_Avoid_: Vue app, component, frontend, SPA

**Glue**:
The Drupal behavior script that reads the Widget's settings, applies Auto-add, and mounts the
Bundle. It is the only bridge between Drupal and the Bundle.
_Avoid_: behavior, shim, adapter, bridge
