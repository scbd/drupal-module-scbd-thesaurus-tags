---
type: project
references: [docs/CONTEXT.md, docs/architecture.md, docs/adr/, README.md]
date: 2026-06-24
---

# SCBD Thesaurus Tags - Project PRD

> This is the umbrella PRD for the
> `scbd_field` Drupal module (repo `drupal-module-scbd-thesaurus-tags`; the machine name in all
> code is `scbd_field`) and its mounted Vue widget bundle (the separate scbd-field-js repo). It is
> a **living** document - append to it as features land; do not fork a parallel copy. Vocabulary:
> [docs/CONTEXT.md](./CONTEXT.md); architecture: [docs/architecture.md](./architecture.md).

## Problem Statement

Editors across the Bioland/Biosafety Land family of Drupal sites need to tag content
(news, articles, documents, organizations, etc.) with terms from the **Secretariat
of the Convention on Biological Diversity (SCBD) controlled vocabularies** -
Global Biodiversity Framework (GBF) targets, Sustainable Development Goals (SDGs),
national biodiversity targets, countries, thematic subjects, IUCN ecosystem types,
Biosafety Clearing-House (BCH) subject groups, and more.

Without a dedicated field, editors would either:

- type free-text tags, producing inconsistent, unsearchable, untranslated metadata
  that cannot be cross-referenced between sites or reported on against the GBF; or
- be forced to maintain a sprawling set of local Drupal taxonomy vocabularies that
  drift out of sync with the authoritative thesauri published on `api.cbd.int`.

The editorial experience also has hard requirements that a generic text or
reference field cannot meet:

- **Stable identifiers, not labels** - the value persisted to the database must be
  the term's canonical key (e.g. `GBF-TARGET-03`, `SUSTAINABLE-DEVELOPMENT-GOAL-06`,
  or a national-target UUID), so reporting and cross-site enrichment keep working
  even as display labels change or are translated.
- **Multilingual labels** - a Belgian Bioland site renders the same term in NL/FR/DE/EN;
  the picker must show locale-appropriate names with English fallback while still
  saving the language-neutral key.
- **Site-scoped / derived vocabularies** - national targets must be filtered to the
  site's country; the set and order of vocabularies ("domains") shown differs between
  a standard CBD site and a biosafety (BCH) site.
- **GBF auto-linking** - picking a GBF target should automatically pull in the SDGs
  and subjects it maps to, so editors do not have to know the cross-walk by heart.
- **Sensible defaults on new content** - biosafety sites want GBF Target 17 and the
  site's country pre-selected on brand-new entities.

## Solution

A Drupal field **type + widget** (`scbd_field_thesaurus` / `scbd_thesaurus_widget`)
whose authoring UI is a **mounted Vue 3 application** delivered as a single IIFE
bundle. The split is deliberate:

- **The Drupal module** (`scbd_field`) owns the field's storage schema, the form
  element, admin configuration (which domains, in what order; auto-add toggles;
  debug), per-site context (country, enabled languages, current locale, biosafety
  flag), the pre-mount seeding of auto-add / initial values, and the loading of the
  Vue bundle as a library.
- **The Vue bundle** (`ScbdDrupalScbdFieldJs`) owns the interactive picker: one
  `vue-multiselect` dropdown per configured domain, fetching live terms from
  `api.cbd.int`, resolving multilingual labels, performing GBF->SDG/Subject
  auto-linking, and reading/writing the saved value.

The two halves communicate through a single, narrow, **hidden-input** contract
(detailed under Implementation Decisions): the Drupal-rendered `value` textfield
holds a comma-separated list of term keys; the Vue app reads it on mount and writes
it back on every change; Drupal persists it to the field's `value` column on save.
There is **no separate `<input>` element, no Twig markup, and no controller/route**
involved in the round-trip - the description-template subsystem named in the legacy
README is dead code left only as empty stubs.

Stable term keys flow through unchanged, so the same value round-trips on every edit;
older SDG keys (`SDG-GOAL-*`) are migrated transparently to
`SUSTAINABLE-DEVELOPMENT-GOAL-*` on read.

## User Stories

### Editing & tagging

1. As a drupal admin, I want to add an "SCBD Thesaurus" field to a content type, so that authors of that type can tag content with controlled-vocabulary terms.
2. As a content manager, I want a labeled dropdown per vocabulary domain (GBF targets, SDGs, national targets, countries, subjects, ...), so that I can find and pick terms by category instead of from one giant list.
3. As a content manager, I want to type to search within a domain's dropdown, so that I can locate a term quickly in a long list.
4. As a content manager, I want to select multiple terms in a multi-select domain, so that one piece of content can be tagged with several relevant terms.
5. As a content manager, I want single-value domains (e.g. ecosystem types, statuses, scopes) to allow exactly one choice, so that the data stays consistent with how that vocabulary is meant to be used.
6. As a content manager, I want grouped domains (e.g. BCH subject groups) to show group headers and let me select a whole group at once, so that I can tag broad themes efficiently.
7. As a content manager, I want the widget to show help text above the dropdowns, so that I understand what the field is for.
8. As a content manager, I want the raw text input that stores the value to be hidden by default, so that the form is clean and I am not tempted to edit keys by hand.

### Value round-trip & persistence

9. As a content manager, I want the terms I selected to be saved when I save the entity, so that my tagging is not lost.
10. As a content manager, I want my previously saved terms to reappear, fully resolved to their human labels, when I re-open content for editing, so that I can see and adjust existing tags.
11. As a system, I want to persist stable term identifiers (keys / UUIDs) rather than display labels, so that tags remain valid across label changes, translations, and cross-site reporting.
12. As a content manager, I want re-saving content without changes to leave the stored value intact, so that an edit does not silently corrupt or reorder my tags.
13. As a drupal admin, I want legacy SDG keys (`SDG-GOAL-NN`) to be read transparently and rewritten to the current `SUSTAINABLE-DEVELOPMENT-GOAL-NN` form on next save, so that old data keeps working without a manual migration.
14. As a system, I want a field item to count as empty only when it holds no value at all, so that empty fields do not produce spurious stored rows.

### Multilingual labels

15. As a content manager on a multilingual site, I want term labels rendered in my current interface language, so that I can tag content in the language I am working in.
16. As a content manager, I want a term to fall back to another enabled language (ultimately English, then the raw key) when no label exists for my current locale, so that I never see a blank option.
17. As a content manager, I want the domain/group **labels themselves** (the dropdown captions) localized for my interface language, so that the whole widget reads naturally.
18. As a system, I want the set of fallback languages to come from the site's actually-enabled languages (as 2-letter ISO codes), so that label resolution matches the site configuration.

### Scoped / derived vocabularies

19. As a content manager on a national (Bioland) site, I want the national-targets dropdown to show only my country's targets, so that I am not offered irrelevant targets from other countries.
20. As a site manager, I want the field's country scope to come from the site's `bioland.settings`, so that scoping is configured once per site and applies everywhere the field is used.
21. As a system, I want national targets to be looked up by UUID, so that targets without a human-friendly slug are still uniquely identifiable.

### Domain configuration (admin)

22. As a site manager, I want a settings form to choose which domains appear and in what order, so that each site shows only the vocabularies it cares about.
23. As a site manager, I want the settings form to reject any domain id that is not on the supported whitelist, so that a typo cannot silently break the widget.
24. As a site manager, I want a reference list of all valid domain keys shown on the settings form, so that I know exactly what I can enter.
25. As a site manager on a biosafety site, I want to be offered the biosafety default domain order when I have not saved one, so that BCH sites get a sensible starting configuration.
26. As a site manager, I want a clearly-scoped permission (`administer scbd field settings`) gating the settings form, so that only trusted roles can change global field behavior.
27. As a site manager, I want the settings form reachable from Configuration -> Content authoring, so that I can find it where Drupal admins expect content-related config.

### GBF auto-linking

28. As a content manager, I want selecting a GBF target to automatically add its related SDGs and subjects, so that I do not have to memorize and apply the GBF<->SDG/Subject cross-walk by hand.
29. As a content manager, I want auto-linking to be additive only - existing selections are preserved and de-selecting a GBF target does not strip linked terms - so that the widget never removes a tag I made on purpose.
30. As a content manager, I want auto-linking to be one-way (only GBF picks pull in related terms; picking an SDG or subject pulls in nothing), so that the behavior is predictable and does not cascade.
31. As a system, I want the GBF->related cross-walk resolved from a local mapping table, so that auto-linking works without an extra network round-trip.

> **Source.** GBF auto-link lives in the field-js bundle: `src/utils/relations.js` reads the `GBF_SAMEAS` cross-walk table from `src/utils/constants.js`, scopes linked terms to `LINKABLE_DOMAINS = ['sdgs', 'subjects']`, and applies them additively + one-way (GBF picks pull in SDGs/subjects; the inverse pulls in nothing).

### Auto-add defaults on new content

32. As a content manager on a biosafety site creating new content, I want GBF Target 17 pre-selected, so that the standard biosafety reporting target is captured by default.
33. As a content manager on a single-country biosafety site creating new content, I want the site's country pre-selected, so that national attribution is captured without manual work.
34. As a site manager, I want to be able to disable auto-add of GBF Target 17 and/or countries, so that I can opt out when the defaults do not fit a site.
35. As a system, I want auto-add applied only to new entities (not existing ones) and only when the value is not already present, so that editing old content does not inject unexpected tags or duplicates.
36. As a system, I want auto-add of a country to happen only when the site has exactly one country, so that ambiguous multi-country sites are not given an arbitrary default.

### Biosafety-context behaviour

37. As a system, I want the widget to detect biosafety mode from `bioland.settings.is_biosafety_land`, so that domain defaults and auto-add behavior adapt to BCH sites automatically.
38. As a site manager on a biosafety site, I want a different default domain set (BCH subject groups, GBF targets, national targets, countries) than a standard site, so that the picker matches the biosafety content managerial workflow.

### Loading, error & debug states

39. As a content manager, I want the picker to mount automatically when the edit form loads, so that I do not have to take any action to get the controlled-vocabulary UI.
40. As a system, I want the widget guarded against mounting twice on the same element, so that AJAX-driven form re-renders do not stack multiple Vue apps.
41. As a system, I want a clear console error (rather than a silent failure or a thrown exception) when Vue or the component bundle is missing, when the mount element is absent, or when the hidden input cannot be found, so that integration problems are diagnosable.
42. As a system, I want a malformed field machine-name to be rejected up front so the widget degrades gracefully instead of building an unsafe selector, so that a bad field name cannot break or hijack the DOM query.
43. As a site manager, I want a debug mode that reveals the raw stored input(s), so that I can inspect exactly what keys are being saved when troubleshooting.
44. As a drupal administrator, I want a field-mismatch diagnostic script that reports the entity field definition versus the actual database columns, so that I can detect and explain storage drift (notably the `value2` column).

### Bundle delivery & versioning

45. As a drupal administrator, I want the Vue bundle published as a single IIFE that exposes one global, so that Drupal can load it as a plain script library alongside a CDN-provided Vue.
46. As a drupal administrator, I want Vue provided externally (CDN global) rather than bundled, so that the widget bundle stays small and Vue is shared.
47. As a drupal administrator, I want the module to load a versioned bundle artifact, so that the field type, widget, and front-end stay in lockstep at a known version.

## Implementation Decisions

### Architecture: Drupal field + mounted Vue bundle

- The product is two repositories with one integration: a **Drupal field plugin pair**
  (`scbd_field_thesaurus` field type + `scbd_thesaurus_widget` widget) and a **Vue 3
  IIFE bundle** the widget mounts. The module is the integration host; the bundle is
  the interactive UI.
- The bundle exposes exactly one global, **`ScbdDrupalScbdFieldJs`**, whose `default`
  export is the Vue component. (This name is set by the bundler's library config, not
  derived from the package name.) The host mounts it with `Vue.createApp(App, props).mount(...)`.
  **Vue itself is a separate external global** loaded from a CDN - it is not bundled.
- The widget renders **one Vue app per field**. A second value (`value2`) is supported
  in code but is **never mounted at runtime** (see "Latent value2" below).

### THE INTERFACE CONTRACT (authoritative)

The integration between the module and the bundle is a **hidden-input contract**, not
a props contract. This is the canonical, current interface:

- **Mount target.** The widget renders the mount element as a `#suffix` on the `value`
  textfield - **not via a Twig template** (the description template is intentionally
  empty). The element is `<div id="scbd-field-thesaurus-{name}">`, where
  `name = lowercased field machine-name with the leading "field_" stripped`
  (e.g. `field_tags` -> `tags`). The Drupal behavior builds the selector
  `#scbd-field-thesaurus-{name}` and mounts into it, guarding against double-mount by
  marking the element `__vue_app__`.
- **State source of truth.** The single hidden Drupal `value` textfield -
  rendered with name `field_{name}[0][value]` and id `edit-field-{name}-0-value` -
  **is** the hidden input. It is the one and only carrier of widget state. There is no
  separate input, no Twig-emitted markup.
- **Value format.** A **comma-separated string of stable term keys**. The bundle reads
  by `split(',')` (dropping empties) and writes by `join(',')`; identifiers are
  de-duplicated via a Set before writing. National targets appear as UUIDs; other
  domains as slugs (`GBF-TARGET-03`, `SUSTAINABLE-DEVELOPMENT-GOAL-06`, ...).
- **Read (hydrate).** On mount the bundle locates the hidden input, fetches each
  domain's options, then resolves the saved keys to full term objects per domain
  (national targets filtered from loaded options; BCH subject groups resolved via a
  BCH-subjects lookup matched to group children; other domains via a term lookup).
- **Write (persist).** Every select / remove / close writes the flattened, de-duped
  selection back into the hidden input's `.value`. Drupal then saves that string into
  the field's `value` column on entity save.
- **Pre-mount seeding.** The module/glue writes the hidden input **before** Vue mounts -
  merging server-computed auto-add values into the initial value - so the bundle simply
  reads an already-correct value. Auto-add and initial-value are therefore **features
  realized through the hidden input, not through props.**
- **Bundle inputs actually consumed (the real prop surface):** `name` (required,
  load-bearing - drives both the multiselect ids and the hidden-input selector),
  `description`, `countries`, `locale`, `locales`, `domains`. `isAdditionalField` is
  declared and consumed but hardcoded `false` by the host, so its `value2` branch is
  dead at runtime. `debug` is declared by the bundle but **not forwarded** by the host,
  so it is effectively off in production (the host hides the raw inputs via a CSS class
  instead). `singleValueDomains` is declared only on the inner component with a fixed
  default and is **not** forwarded from Drupal, so single-select membership is currently
  a build-time constant, not site-configurable.

### THE SOURCE-OF-TRUTH RULE (decisive)

**The module's actual runtime usage is the source of truth for the interface, not stale
comments and not the superset of props the glue happens to pass.**
Where the glue's `createApp` call passes more than the bundle actually consumes, the
module's real behavior wins; the drift is recorded and the canonical resolution is stated.
Concretely, the current contract is **narrower** than the glue's `createApp` call:

- Props the glue passes but the bundle does not consume: `fullFieldName`,
  `initialValue`, `initialValue2`, `autoAddValues`, `additionalFieldName`. These are
  no-ops on the bundle. The corresponding **features** (auto-add, initial value) are
  real and working, but via hidden-input pre-seeding, **not** via these props.
  `fullFieldName` is a glue-internal concern (used only for the glue's own pre-mount
  hidden-field sync); the bundle reconstructs `field_{name}` from `name`.
- Behavior that is **retired or not host-controllable**: the combined `singleField` mode
  is gone from current bundle source ("single fields" now means single-value domains
  rendered one-per-widget), and `singleValueDomains` is never supplied by this module's
  glue or widget, so the bundle's own default applies and single-select membership is a
  build-time bundle constant rather than a Drupal-controllable prop.
- Facts verifiable from this repo: the settings route is
  `/admin/config/content/scbd-field` (`scbd_field.routing.yml`); the module currently
  ships a **local, stale** copy of the bundle (an older build that still contains
  `singleField`) loaded from `scbd_field.libraries.yml`. Whatever the separate bundle
  README asserts about prop declarations or delivery lives outside this repo and is not
  restated here.
- **This module's own `README.md` is the most stale doc of all.** It describes a
  non-existent autocomplete/typeahead PHP widget, a "remote thesaurus API base URL"
  setting that the settings form does not have (the real settings are `debug`, the two
  auto-add toggles, and `domain_order`), a live `description.html.twig` (it is empty),
  and `scbd_field.js` / `index.min.js` artifact names that do not match the shipped
  `scbd_field-2-0-9.js` and `js/vue-field/index.min-2-0-9.js.iife.js`. Treat
  `docs/architecture.md` and this PRD as authoritative over the module README; rewriting
  the README from them is deferred follow-up work (see Out of Scope).

### Field type & storage schema

- Field type `scbd_field_thesaurus`: two columns, **`value`** and **`value2`**, both
  `text/big`, nullable. Property definitions expose both as strings. The item is empty
  only when **both** are empty. Default widget `scbd_thesaurus_widget`, default
  formatter `string`. At the storage layer the DB columns are `field_{name}_value` and
  `field_{name}_value2`.
- **Latent `value2`.** The `value2` column, the widget's `value2` textfield, the
  bundle's `isAdditionalField`/`value2` code path, and install update-hooks that
  backfill a missing `value2` column on existing installs all exist - but `value2` is
  **runtime-dead**: no second app is ever mounted (`isAdditionalField` is hardcoded
  `false`). It is storage- and code-ready but unused. (See Out of Scope and the open
  question on whether to wire or remove it.)

### Widget, settings & context

- The widget builds the two textfields and emits the mount `<div>` as `#suffix` on
  `value`; it attaches the `scbd_field/thesaurus` library and `drupalSettings.scbd_field`
  (field name with/without prefix, description, initial values, countries from
  `bioland.settings`, enabled locales as 2-letter codes, current locale, configured
  domain order, debug, and computed auto-add values). Both textfields get a `hide` CSS
  class unless debug - this CSS class, **not** the bundle's `debug` prop, is what
  visually hides the raw inputs.
- **Cosmetic glue side-effect (outside the contract).** On attach, the glue also runs
  `hideTextFormat()` (`scbd_field-2-0-9.js:220-225`), which hard-codes the
  `edit-body-0-format` selectors (`label[for="edit-body-0-format--2"]` and
  `#edit-body-0-format-help-about`) to suppress the body field's text-format label and
  help link. This is a purely cosmetic DOM tweak, unrelated to the hidden-input
  round-trip, and is arguably outside the module<->bundle interface contract.
- The settings form (config object `scbd_field.settings`) owns: debug toggle,
  `disable_auto_gbf17`, `disable_auto_countries`, and `domain_order` (one domain id per
  line). The supported-domain whitelist is the form's `getDomainOptions()`:
  `gbfTargets, nationalTargets7, sdgs, countries, subjects, bchSubjects,
  bchSubjectGroups, regions, ecosystemTypes`. Standard default order:
  `gbfTargets, nationalTargets7, countries, subjects, sdgs`. Biosafety default order:
  `bchSubjectGroups, gbfTargets, nationalTargets7, countries`. Validation rejects any
  domain id outside the whitelist.
- **Biosafety detection at runtime keys on the site flag, not the helper.** The widget
  computes `$is_biosafety` inline from `bioland.settings.is_biosafety_land` and gates
  Auto-add on that flag for new entities. A separate `BiosafetyContext::isBiosafetyContext()`
  helper exists (and is unit-tested) that also treats a field as biosafety when its domains
  include `bchSubjects` / `bchSubjectGroups`, but **no production code calls it** - so the
  domain-based half of the biosafety definition is not active in the Auto-add path today.
  Either wire the helper into the widget or treat it as documentation of intended behaviour.

### Dead subsystems (do not resurrect)

- `templates/description.html.twig` is intentionally empty and renders nothing;
  `ScbdFieldController` and `DescriptionTemplateTrait` are empty deprecated stubs kept
  only to avoid fatals from stale cached routes; the only route maps to the settings
  form. The entire description-template/controller subsystem named in the legacy README
  is dead code.

### Bundle build & delivery

- The bundle builds to a single IIFE (`dist/index.min.js`) plus `style.css` and a
  source map; Vue is marked external. CI is intended to publish those artifacts to a
  versioned GitHub release that the module's library references. **Today, however, the
  module loads a local committed copy of the bundle that is stale relative to the
  bundle source** - choosing and enforcing one delivery mechanism, and re-syncing the
  shipped artifact, is a known follow-up.
- **Concrete evidence of the stale shipped build.** The committed
  `js/vue-field/index.min-2-0-9.js.iife.js` is roughly 262 KB and still contains the
  **retired `singleField` path** (8 occurrences via `grep -o singleField`). A fresh
  `dist` build from the current bundle source is about half that size with **zero**
  `singleField` occurrences. The shipped IIFE is therefore roughly twice the size of a
  fresh build, which is measurable proof that the shipped artifact lags `src/` and
  carries removed code. (Exact bytes and dates drift on every rebuild and are not pinned
  here.)

## Testing Decisions

- **Test external behavior at the contract seam, not implementation details.** The
  highest-value seam is the **hidden-input round-trip**: given a hidden input
  pre-seeded with comma-separated keys, mounting the bundle should hydrate the
  multiselects to the matching terms; user selections should write the de-duped,
  comma-joined keys back into the same input. Asserting on the hidden input's `.value`
  (and on rendered option labels) tests the real contract without coupling to internals.
- **Bundle unit/component tests** (Vitest, as already configured in the bundle repo)
  should cover: hydration from a seeded hidden input, persistence on select/remove/close,
  GBF->SDG/Subject auto-linking (additive, one-way), single- vs multi-select per domain,
  grouped-domain (BCH groups) selection, multilingual label resolution with fallback,
  SDG legacy-key migration, and graceful degradation on a missing hidden input or
  invalid field name (console error, no throw). The bundle's existing dev harness
  (which simulates the Drupal hidden inputs) is the model to copy for fixtures.
- **Module PHP tests** (PHPUnit, as configured) should cover the widget's render array
  (mount div id, hidden input name/id pattern, attached library and `drupalSettings`),
  the field type's schema / `isEmpty` semantics, and the settings form's domain whitelist
  validation and biosafety defaults.
- **Diagnostic as a test aid.** The existing field-mismatch diagnostic script is prior
  art for verifying entity-definition-vs-DB-column alignment (the `value2` drift); keep
  it runnable as a maintenance check.
- A good test here is one that survives a refactor of the bundle internals as long as
  the **comma-separated-keys-in-one-hidden-input** contract holds.

## Out of Scope

- **Rewriting the module `README.md` or the bundle README / reconciling the bundle to the
  contract.** This PRD only records the canonical contract and the drift; rewriting either
  README (this module's is badly out of date - see the Source-of-Truth section), trimming
  the glue's no-op props, wiring `debug`/`singleValueDomains` through, or re-syncing the
  shipped artifact are deliberately deferred to later, separately-scoped work.
- **Wiring the `BiosafetyContext` helper into the widget.** The helper and its tests exist
  but nothing calls them; whether to route the widget's biosafety detection through it
  (so the domain-based trigger counts) or to delete it as dead is a future decision.
- **Activating `value2` / a second value.** No second app is mounted today. Deciding to
  wire a second mount (`isAdditionalField: true`) **or** to remove `value2` (field type
  column, widget element, backfill install hooks) is a future decision, not part of the
  current product.
- **Restoring the description-template / controller subsystem.** It is dead and stays dead.
- **A combined single-field ("`singleField`" / "all") widget mode.** Retired from the
  current bundle source; not part of the product going forward (pending confirmation no
  live site depends on the older shipped build).
- **Making single-select domain membership site-configurable from Drupal.** Currently a
  fixed bundle default; exposing it as a Drupal setting is a possible future enhancement.
- **Authoring or hosting the thesauri themselves.** The vocabularies are published by
  `api.cbd.int`; this product consumes them, it does not manage them.

## Further Notes

- **Hypothesis check.** The working hypothesis was "the bundle code is right and only
  the docs need updating." Verdict: **mostly confirmed but incomplete.** The bundle code
  IS the current source of truth (it reads/writes the hidden input directly; the legacy
  props are genuinely absent from the built bundle). But "just update the docs"
  undercounts the drift: the **glue** still passes six props the bundle ignores (one,
  `additionalFieldName`, not part of any contract), the **shipped bundle is an older
  build** than source (still contains `singleField`), and two features the props surface
  implies are live (auto-add, initial value) are real but implemented via hidden-input
  pre-seeding. So the picture is wrong about the **mechanism**, not about the features
  existing, and the full fix is docs **plus** a glue trim and a bundle re-sync.
- **Open questions to resolve in follow-up work:** (1) Is `value2` a planned feature
  (wire a second mount) or abandoned (remove it and its backfill hooks)? (2) Canonical
  bundle delivery - local committed artifact vs GitHub-release URL - and who re-syncs
  the shipped IIFE? (3) Should `debug` be forwarded from the glue so the bundle's debug
  UI works in Drupal, or stay dev-harness-only? (4) Should `singleValueDomains` become
  Drupal-configurable or remain a build-time constant? (5) Can the glue stop passing the
  five no-op props once docs are reconciled? (6) Does any live site rely on the older
  shipped combined-field mode before it is removed from source? (7) Are the deprecated
  stubs (controller, description trait, empty Twig) safe to delete now, given the cached-
  route risk on some environments?
