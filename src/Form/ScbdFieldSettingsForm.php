<?php

namespace Drupal\scbd_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\scbd_field\Service\TranslationBatchService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure SCBD Field settings for translation automation.
 */
class ScbdFieldSettingsForm extends ConfigFormBase {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The translation batch service.
   *
   * @var \Drupal\scbd_field\Service\TranslationBatchService
   */
  protected $translationBatchService;

  /**
   * Constructs a new ScbdFieldSettingsForm.
   *
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\scbd_field\Service\TranslationBatchService $translation_batch_service
   *   The translation batch service.
   */
  public function __construct(LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, TranslationBatchService $translation_batch_service) {
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->translationBatchService = $translation_batch_service;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('scbd_field.translation_batch')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['scbd_field.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scbd_field_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('scbd_field.settings');

    $form['countries'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Countries'),
      '#description' => $this->t('Enter country codes, one per line.'),
      '#default_value' => implode("\n", $config->get('countries') ?: []),
    ];

    $form['translation'] = [
      '#type' => 'details',
      '#title' => $this->t('Translation Settings'),
      '#open' => TRUE,
    ];

    $form['translation']['auto_create'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Automatically create translations'),
      '#description' => $this->t('When enabled, translations will be automatically created for entities with SCBD thesaurus fields.'),
      '#default_value' => $config->get('translation.auto_create') ?: FALSE,
    ];

    $form['translation']['use_all_languages'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use all available languages'),
      '#description' => $this->t('When enabled, translations will be created for all languages installed on the site. When disabled, only the selected target languages below will be used.'),
      '#default_value' => $config->get('translation.use_all_languages') ?: TRUE,
      '#states' => [
        'visible' => [
          ':input[name="auto_create"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Get available languages.
    $languages = $this->languageManager->getLanguages();
    $language_options = [];
    foreach ($languages as $langcode => $language) {
      $language_options[$langcode] = $language->getName();
    }

    $form['translation']['target_languages'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Target languages'),
      '#description' => $this->t('Select which languages to create translations for. This is only used when "Use all available languages" is disabled.'),
      '#options' => $language_options,
      '#default_value' => array_combine(
        $config->get('translation.target_languages') ?: [],
        $config->get('translation.target_languages') ?: []
      ),
      '#states' => [
        'visible' => [
          ':input[name="auto_create"]' => ['checked' => TRUE],
          ':input[name="use_all_languages"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['translation']['copy_source_values'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Copy source field values'),
      '#description' => $this->t('When enabled, SCBD thesaurus field values from the source language will be copied to new translations.'),
      '#default_value' => $config->get('translation.copy_source_values') ?: FALSE,
      '#states' => [
        'visible' => [
          ':input[name="auto_create"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // Get content entity types.
    $entity_types = $this->entityTypeManager->getDefinitions();
    $content_entity_options = [];
    foreach ($entity_types as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements('Drupal\Core\Entity\ContentEntityInterface')) {
        $content_entity_options[$entity_type_id] = $entity_type->getLabel();
      }
    }

    $form['translation']['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Entity types'),
      '#description' => $this->t('Select which entity types should have automatic translations created.'),
      '#options' => $content_entity_options,
      '#default_value' => array_combine(
        $config->get('translation.entity_types') ?: [],
        $config->get('translation.entity_types') ?: []
      ),
      '#states' => [
        'visible' => [
          ':input[name="auto_create"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['batch_operations'] = [
      '#type' => 'details',
      '#title' => $this->t('Batch Operations'),
      '#description' => $this->t('Process existing entities to create missing translations.'),
      '#open' => FALSE,
    ];

    $form['batch_operations']['batch_entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Entity type to process'),
      '#options' => ['' => $this->t('- Select -')] + $content_entity_options,
      '#description' => $this->t('Select an entity type to create translations for existing entities.'),
    ];

    $form['batch_operations']['run_batch'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create translations for existing entities'),
      '#submit' => ['::submitBatchForm'],
      '#states' => [
        'visible' => [
          ':input[name="batch_entity_type"]' => ['!value' => ''],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $countries = array_filter(array_map('trim', explode("\n", $form_state->getValue('countries'))));
    $target_languages = array_filter($form_state->getValue('target_languages'));
    $entity_types = array_filter($form_state->getValue('entity_types'));

    $this->config('scbd_field.settings')
      ->set('countries', $countries)
      ->set('translation.auto_create', $form_state->getValue('auto_create'))
      ->set('translation.use_all_languages', $form_state->getValue('use_all_languages'))
      ->set('translation.target_languages', array_values($target_languages))
      ->set('translation.copy_source_values', $form_state->getValue('copy_source_values'))
      ->set('translation.entity_types', array_values($entity_types))
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Submit handler for batch operations.
   */
  public function submitBatchForm(array &$form, FormStateInterface $form_state) {
    $entity_type_id = $form_state->getValue('batch_entity_type');
    
    if (empty($entity_type_id)) {
      $this->messenger()->addError($this->t('Please select an entity type to process.'));
      return;
    }

    $batch = $this->translationBatchService->createTranslationBatch($entity_type_id);
    batch_set($batch);
  }

}
