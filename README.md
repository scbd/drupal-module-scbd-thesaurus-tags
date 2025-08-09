# SCBD Thesaurus Tags (Drupal Module)

Provides a configurable field/widget pair to tag Drupal content entities with terms coming from the SCBD online thesaurus / taxonomy service. Designed for editorial UX (typeahead + description templates) and consistent metadata tagging across CBD sites.

## Key Features

- Autocomplete / typeahead widget for thesaurus terms
- Stores stable term identifiers (not just labels)
- Description template support (Twig) via `templates/description.html.twig`
- Admin settings form (`ScbdFieldSettingsForm`) for API endpoint & display options
- Lightweight JS behavior (`scbd_field.js`) with compiled artifact `index.min.js`

## Installation

1. Copy / require the module into `modules/custom` (already present in this repo if you're reading here).
2. Enable the module:
   - Via UI: Extend -> SCBD Thesaurus Tags
   - Or Drush: `drush en scbd_field -y`
3. Grant permissions as needed (if any are introduced later).

## Configuration

Navigate to: Configuration -> Content Authoring -> SCBD Thesaurus Field Settings

Adjust:

- Remote thesaurus API base URL
- Result size / limit
- Description rendering options

## Usage

1. Add the SCBD Thesaurus field to a content type (Manage Fields -> Add field).
2. Choose the SCBD thesaurus field type / widget.
3. While editing content, begin typing to search remote terms; select from suggestions.
4. Saved entity stores term IDs; renderers can fetch full metadata as needed.

## Front-End Assets

- Source behavior: `scbd_field.js`
- Distributed/minified bundle: `index.min.js`
- Styles (example skin): `style-1-1-4-a.css`

If you modify JS, rebuild/minify (add your build step here if tooling introduced). Current repo ships pre-built assets.

## Templates

`templates/description.html.twig` is used for dynamic description output. Override in your theme if required.

## Testing

JavaScript tests: see `__tests__/scbd_field.test.js`
PHP Unit tests: run with `vendor/bin/phpunit -c phpunit.xml.dist`

## Versioning

Active development branch: `BL-605-2.0.0-alpha`
Follow Conventional Commits (e.g., `feat:`, `fix:`, `docs:`) to aid semantic versioning.

## Contributing

1. Create a feature branch
2. Add/adjust tests
3. Ensure lint/build (when added) pass
4. Open a PR against `master` (or designated integration branch)

## License

See `LICENSE` file (compatible OSS license enclosed).

## Maintainers

SCBD / BL2 engineering team.

---
Feel free to extend this README with API contract details or architecture diagrams as the module evolves.
