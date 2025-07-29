<?php

namespace Drupal\scbd_field\Service;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Service for managing automatic translations of entities with SCBD fields.
 */
class TranslationManager {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new TranslationManager.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LanguageManagerInterface $language_manager, LoggerChannelFactoryInterface $logger_factory, EntityTypeManagerInterface $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->languageManager = $language_manager;
    $this->loggerFactory = $logger_factory;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Creates translations for an entity if configured to do so.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to create translations for.
   * @param string $operation
   *   The operation being performed (insert, update, etc.).
   *
   * @return bool
   *   TRUE if translations were created, FALSE otherwise.
   */
  public function createTranslations(ContentEntityInterface $entity, $operation = 'insert') {
    $config = $this->configFactory->get('scbd_field.settings');
    
    // Check if auto-translation is enabled
    if (!$config->get('translation.auto_create')) {
      return FALSE;
    }

    // Check if this entity type should be auto-translated
    $enabled_types = $config->get('translation.entity_types') ?: [];
    if (!in_array($entity->getEntityTypeId(), $enabled_types)) {
      return FALSE;
    }

    // Only process translatable entities
    if (!$entity->isTranslatable()) {
      return FALSE;
    }

    // Check if entity has SCBD thesaurus fields
    if (!$this->entityHasScbdFields($entity)) {
      return FALSE;
    }

    // Determine target languages based on configuration
    $use_all_languages = $config->get('translation.use_all_languages') ?: FALSE;
    $configured_target_languages = $config->get('translation.target_languages') ?: [];
    
    if ($use_all_languages) {
      $available_languages = $this->languageManager->getLanguages();
      $target_languages = array_keys($available_languages);
    } else {
      $target_languages = $configured_target_languages;
    }
    
    $copy_source_values = $config->get('translation.copy_source_values') ?: FALSE;
    $source_language = $entity->language();
    $created_count = 0;

    foreach ($target_languages as $langcode) {
      // Skip if it's the same as source language
      if ($langcode === $source_language->getId()) {
        continue;
      }

      // Check if the language exists
      $language = $this->languageManager->getLanguage($langcode);
      if (!$language) {
        continue;
      }

      // Check if translation already exists
      if ($entity->hasTranslation($langcode)) {
        continue;
      }

      try {
        // Create the translation
        $translation_values = $this->prepareTranslationValues($entity, $langcode, $copy_source_values);
        $translation = $entity->addTranslation($langcode, $translation_values);
        
        // Save the translation
        $translation->save();
        $created_count++;

        $this->loggerFactory->get('scbd_field')->info(
          'Auto-created @langcode translation for @entity_type @entity_id',
          [
            '@langcode' => $langcode,
            '@entity_type' => $entity->getEntityTypeId(),
            '@entity_id' => $entity->id(),
          ]
        );
      }
      catch (\Exception $e) {
        $this->loggerFactory->get('scbd_field')->error(
          'Failed to create @langcode translation for @entity_type @entity_id: @message',
          [
            '@langcode' => $langcode,
            '@entity_type' => $entity->getEntityTypeId(),
            '@entity_id' => $entity->id(),
            '@message' => $e->getMessage(),
          ]
        );
      }
    }

    return $created_count > 0;
  }

  /**
   * Checks if an entity has SCBD thesaurus fields.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity to check.
   *
   * @return bool
   *   TRUE if the entity has SCBD fields, FALSE otherwise.
   */
  protected function entityHasScbdFields(ContentEntityInterface $entity) {
    foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
      if ($field_definition->getType() === 'scbd_field_thesaurus') {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Prepares translation values for a new translation.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The source entity.
   * @param string $langcode
   *   The target language code.
   * @param bool $copy_source_values
   *   Whether to copy source field values.
   *
   * @return array
   *   The translation values.
   */
  protected function prepareTranslationValues(ContentEntityInterface $entity, $langcode, $copy_source_values) {
    $values = [];

    // Always copy the title/label with language indicator
    $label_field = $entity->getEntityType()->getKey('label');
    if ($label_field && $entity->hasField($label_field)) {
      $language = $this->languageManager->getLanguage($langcode);
      $original_title = $entity->get($label_field)->value;
      $values[$label_field] = $original_title . ' (' . $language->getName() . ')';
    }

    // If configured, copy SCBD field values
    if ($copy_source_values) {
      foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
        if ($field_definition->getType() === 'scbd_field_thesaurus') {
          $field_value = $entity->get($field_name)->getValue();
          if (!empty($field_value)) {
            $values[$field_name] = $field_value;
          }
        }
      }
    }

    return $values;
  }

  /**
   * Gets the list of configured target languages.
   *
   * @return array
   *   Array of language codes.
   */
  public function getTargetLanguages() {
    $config = $this->configFactory->get('scbd_field.settings');
    $use_all_languages = $config->get('translation.use_all_languages') ?: FALSE;
    
    if ($use_all_languages) {
      $available_languages = $this->languageManager->getLanguages();
      return array_keys($available_languages);
    } else {
      return $config->get('translation.target_languages') ?: [];
    }
  }

  /**
   * Checks if auto-translation is enabled.
   *
   * @return bool
   *   TRUE if enabled, FALSE otherwise.
   */
  public function isAutoTranslationEnabled() {
    $config = $this->configFactory->get('scbd_field.settings');
    return $config->get('translation.auto_create') ?: FALSE;
  }

}
