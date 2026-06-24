---
status: accepted
date: 2026-06-23
deciders: [scbd-bl2-team]
context: system-wide
code-path: src/Plugin/Field/FieldWidget/, scbd_field-2-0-9.js, scbd_field.libraries.yml
origin: standalone
---

# 0003. Render the picker as a Vue bundle mounted into the Drupal Widget

The Widget renders a hidden Drupal textfield plus an empty mount element, and the Glue mounts a
prebuilt Vue 3 application (the Bundle) into that element to draw the actual picker. Vue and
vue-multiselect are loaded from a CDN through the library definition. We chose this over a native
Drupal autocomplete or a server-rendered widget so the same SCBD picker can be reused across
platforms and own its own search, multi-select, grouping, and label resolution. The Drupal field
keeps ownership of the stored value; the Bundle reads and writes it through the hidden field.

## Consequences
- The editorial UI depends on third-party CDN availability at form render time.
- Drupal and the Bundle communicate only through the Glue and the hidden field, so prop and column
  drift between the two layers is possible and must be watched (for example the latent `value2`
  column and unused Bundle props).
- Reworking the picker means rebuilding and versioning the Bundle artifact, not editing PHP.

## Erratum (2026-06-24)
The phrase "Vue and vue-multiselect are loaded from a CDN" above is imprecise. Per
`scbd_field.libraries.yml`, the CDN delivers only Vue's JavaScript runtime
(`vue.global.prod.js`, unpkg) and vue-multiselect's CSS (`vue-multiselect.css`, unpkg), plus
Bootstrap's CSS (jsdelivr). vue-multiselect's JavaScript is **not** CDN-loaded; it is bundled
into the IIFE artifact. So the CDN split is: Vue JS + vue-multiselect CSS + Bootstrap CSS from
CDN, with vue-multiselect's JS compiled into the Bundle. The decision itself is unchanged.
