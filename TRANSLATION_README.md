# SCBD Field Translation Features

This module now includes automatic translation functionality for entities with SCBD thesaurus fields.

## Features

### Automatic Translation Creation
When enabled, the module will automatically create translation entries for entities that:
- Are translatable
- Have SCBD thesaurus fields
- Match the configured entity types

### Configuration Options

Access the configuration at: `/admin/config/content/scbd-field`

#### Translation Settings

- **Automatically create translations**: Enable/disable automatic translation creation
- **Use all available languages**: When enabled, creates translations for all languages installed on the site
- **Target languages**: Select specific languages to create translations for (only used when "Use all available languages" is disabled)
- **Copy source field values**: Whether to copy SCBD field values from source to translations
- **Entity types**: Select which entity types should have automatic translations

### How It Works

**By default, the module is configured to use all available languages** when auto-translation is enabled.

1. When an entity with SCBD thesaurus fields is created or updated
2. The module checks if auto-translation is enabled
3. If "Use all available languages" is enabled (default), it gets all installed languages
4. If "Use all available languages" is disabled, it uses the configured target languages
5. For each target language:
   - Creates a new translation entry
   - Optionally copies SCBD field values
   - Sets the translation as unpublished by default
   - Adds language indicator to the title

### Technical Implementation

The feature uses:
- **Entity hooks**: `hook_entity_insert()` and `hook_entity_update()`
- **Translation Manager service**: `scbd_field.translation_manager`
- **Configuration schema**: Stores settings in `scbd_field.settings`
- **Admin form**: Provides UI for configuration

### API Usage

You can also use the translation manager service programmatically:

```php
// Get the translation manager service
$translation_manager = \Drupal::service('scbd_field.translation_manager');

// Create translations for an entity
$translation_manager->createTranslations($entity, 'insert');

// Check if auto-translation is enabled
if ($translation_manager->isAutoTranslationEnabled()) {
  // Do something
}

// Get configured target languages
$languages = $translation_manager->getTargetLanguages();
```

### Helper Functions

The module provides helper functions:

```php
// Check if entity has SCBD fields
if (scbd_field_entity_has_scbd_fields($entity)) {
  // Process entity
}

// Get SCBD field values from entity
$values = scbd_field_get_scbd_field_values($entity);
```

### Logging

Translation creation activities are logged to the 'scbd_field' log channel. Check the logs for:
- Successful translation creation
- Errors during translation creation
- Configuration issues

### Requirements

- Drupal 9.4+ or Drupal 10+
- Content Translation module enabled
- Multiple languages configured on the site
- SCBD thesaurus fields added to translatable entity types
