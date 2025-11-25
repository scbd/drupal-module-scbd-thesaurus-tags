# AI Coding Agent Instructions: SCBD Thesaurus Tags Drupal Module

## Project Overview
This is a Drupal 9/10 custom module (`scbd_field`) that provides a specialized field type and widget for tagging content with terms from the SCBD (Secretariat of the Convention on Biological Diversity) online thesaurus service. The module combines:
- **Backend**: Drupal PHP plugin architecture (FieldType + FieldWidget + ConfigForm)
- **Frontend**: Vue.js 3 widget loaded via Drupal's library system
- **Data Model**: Two-column storage (`value` + `value2`) with unlimited cardinality

## Architecture & Key Components

### Field Plugin Structure (Drupal Standard)
- **FieldType**: `src/Plugin/Field/FieldType/ScbdFieldThesaurus.php` defines storage schema (2 text columns: `value`, `value2`)
- **FieldWidget**: `src/Plugin/Field/FieldWidget/ScbdThesaurusWidget.php` renders form element with Vue.js mount point
- **Config Form**: `src/Form/ScbdFieldSettingsForm.php` at `/admin/config/content/scbd-field` for global settings (countries, locales)

### Frontend Integration Pattern
The widget uses a **hybrid approach** that AI agents must understand:
1. Drupal renders a hidden textfield (`#type: textfield`, CSS class `.hide`) to store selected term data
2. Vue.js app mounts into an adjacent `<div id="scbd-field-thesaurus-{name}">` suffix element
3. JavaScript behavior (`scbd_field.js`) bridges Drupal settings → Vue props via `drupalSettings`
4. Vue component bundle loaded as `ScbdDrupalScbdFieldJs.default` from external CDN (`index.min.js`)

**Critical**: The Vue component source is NOT in this repo—`index.min.js` is a pre-compiled external artifact.

### Data Storage & Migration Logic
The module stores comma-separated term IDs in `value`/`value2` columns, then **normalizes on install/update**:
- `scbd_field.install` contains `_scbd_field_expand_lists_*()` helpers that split CSV data into separate field deltas
- Update hooks (`scbd_field_update_9001`, `9002`) ensure existing data migrates to unlimited cardinality
- When editing migration code: the install file is autoloaded via `composer.json` → `"files": ["scbd_field.install"]`

## Development Workflows

### Testing (Dual Stack)
```bash
# PHP tests (PHPUnit 10.5, PSR-12 linting)
composer test                    # Run tests without coverage
composer run lint:php            # phpcs with PSR12 standard
composer run lint:php-fix        # phpcbf auto-fix

# JavaScript tests (Jest + ESLint 9 flat config)
npm test                         # Jest in CI mode
npm run lint:js                  # ESLint check
npm run lint:js-fix              # ESLint auto-fix

# Run all CI checks locally (Docker-based, mirrors CircleCI)
make ci                          # Runs php-lint, js-lint, php-test, js-test
./ci-local.sh                    # Alternative: bash script version
```

### Git Hooks & Pre-Commit CI
**Installed via**: `make install-hooks` or `scripts/install-git-hooks.sh`
- Pre-commit hook runs `scripts/pre-commit-ci.sh`: lint + test only changed files (PHP/JS)
- Bypass with `SKIP_LOCAL_CI=1 git commit` (emergency override)
- CircleCI workflow (`.circleci/config.yml`) mirrors local Make targets exactly

### Conventional Commits & Branching
- Active branch: `BL-605-2.0.0-alpha` (not `master`)
- Use conventional commit prefixes: `feat:`, `fix:`, `chore:`, `docs:`, `test:`
- Open PRs against `master` after feature work on alpha branch

## Code Patterns & Conventions

### PHP Coding Standards
- **PSR-12** strictly enforced (no Drupal coding standards)
- Namespace: `Drupal\scbd_field\{Plugin|Form|Utility}\...`
- Use `FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED` (not `-1` magic number)
- Config access: `\Drupal::config('scbd_field.settings')->get('countries')`

### JavaScript Patterns (Drupal Behaviors)
```javascript
// Standard Drupal behavior pattern used in scbd_field.js
Drupal.behaviors.scbd_thesaurus_widget = {
  attach: function (_context, settings) {
    // Access config via settings.scbd_field.{countries|locale|locales}
    // Mount Vue only once: check !mountElement.__vue_app__ before creating app
  }
};
```
- **Globals expected**: `Drupal`, `jQuery`, `Vue` (defined in `eslint.config.js` globals)
- CommonJS export pattern for Jest: `if (typeof module !== 'undefined') { module.exports = {...} }`

### Configuration Schema
`config/schema/scbd_field.schema.yml` defines:
- `scbd_field.settings`: countries/locales as string sequences
- Field default value schema: `field.scbd_field_thesaurus.value`
- **Always update schema when adding config keys** (Drupal requirement)

### Translation Files
- PO files in `translations/scbd_field.{en|fr}.po`
- Update hook `scbd_field_update_9003()` refreshes locale imports
- After adding translatable strings: rebuild with `drush locale:update`

## Common Tasks for AI Agents

### Adding a New Config Option
1. Update `ScbdFieldSettingsForm::buildForm()` to add form element
2. Update `submitForm()` to save the value
3. Add schema entry in `config/schema/scbd_field.schema.yml`
4. Pass to JS via `ScbdThesaurusWidget::formElement()` `'#attached' => ['drupalSettings' => ...]`
5. Update `scbd_field.js` to read from `settings.scbd_field.{yourKey}`

### Modifying Field Storage
1. Update `ScbdFieldThesaurus::schema()` for column changes
2. Create update hook in `scbd_field.install` (next available `scbd_field_update_900X`)
3. Add migration logic if data transformation needed
4. Test with `drush updb` after schema change

### Debugging Widget Rendering
- Check browser console for Vue mount errors (Vue loaded from CDN)
- Verify `drupalSettings.scbd_field` object in page source
- Use `hideTextFormat()` function pattern to manipulate sibling Drupal form elements
- Mount element ID format: `#scbd-field-thesaurus-{field_name}` (without `field_` prefix)

## CI/CD & Deployment

### Local Development with Docker
Makefile provides CircleCI job mirrors using official CircleCI images:
- `cimg/php:8.3-node` for PHP linting/testing
- `cimg/node:24.1` for JS linting/testing
- Volume mounts: project + npm/composer caches at `~/.cache/{npm-ci-scbd|composer-scbd}`

### Deployment Commands (Legacy)
**Note**: `package.json` contains `upload*` scripts for rsync/scp to staging servers. These are environment-specific—ask before modifying.

## Critical Files Reference
- **Entry points**: `scbd_field.info.yml` (module definition), `scbd_field.libraries.yml` (asset loading)
- **Core logic**: `scbd_field.install` (migrations), `scbd_field.js` (widget behavior)
- **Data layer**: `ScbdFieldThesaurus.php` (schema), `ScbdThesaurusWidget.php` (form rendering)
- **Test fixtures**: `tests/php/ScbdFieldInstallHelpersTest.php` (CSV splitting), `tests/js/scbd_field.test.js` (DOM manipulation)

## Anti-Patterns to Avoid
- ❌ Don't modify `index.min.js` directly (external artifact, no source in repo)
- ❌ Don't use `drush en scbd_field` without running update hooks after schema changes
- ❌ Don't commit without running `make ci` or accepting pre-commit hook results
- ❌ Don't add Drupal-specific coding standards (this project uses PSR-12)
- ❌ Don't assume `_scbd_field_*` helpers are available outside `scbd_field.install` context
