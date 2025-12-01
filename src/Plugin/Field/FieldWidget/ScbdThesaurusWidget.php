<?php

namespace Drupal\scbd_field\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'scbd_thesaurus_widget' widget.
 *
 * @FieldWidget(
 *   id = "scbd_thesaurus_widget",
 *   module = "scbd_field",
 *   label = @Translation("Thusaurus widget multiselect"),
 *   field_types = {
 *     "scbd_field_thesaurus"
 *   }
 * )
 */
class ScbdThesaurusWidget extends WidgetBase
{
  /**
   * {@inheritdoc}
   */
    public function formElement(
        FieldItemListInterface $items,
        $delta,
        array $element,
        array &$form,
        FormStateInterface $form_state
    ) {
        $value = $items[$delta]->value ?? '';
        $value2 = $items[$delta]->value2 ?? '';
        $field_name = str_replace('field_', '', strtolower($this->fieldDefinition->getName()));
        $full_field_name = strtolower($this->fieldDefinition->getName());
        $entity = $items->getEntity();
        $is_new_entity = $entity ? $entity->isNew() : false;
        
        // Get countries from bioland.settings
        $bioland_config = \Drupal::config('bioland.settings');
        $countries = $bioland_config->get('countries') ?: [];
        $is_biosafety = $bioland_config ? (bool) $bioland_config->get('is_biosafety_land') : false;
        
        $config = \Drupal::config('scbd_field.settings');
        $disable_auto_countries = $config->get('disable_auto_countries') ?: false;
        $disable_auto_gbf17 = $config->get('disable_auto_gbf17') ?: false;
        
        // Get locales from Drupal's enabled languages and convert to 2-letter ISO codes
        $language_manager = \Drupal::languageManager();
        $enabled_languages = $language_manager->getLanguages();
        $locales = [];
        foreach ($enabled_languages as $language) {
            $langcode = $language->getId();
            // Convert to 2-letter ISO code (e.g., 'en-us' -> 'en', 'fr-ca' -> 'fr')
            $iso_code = substr($langcode, 0, 2);
            if (strlen($iso_code) === 2 && !in_array($iso_code, $locales)) {
                $locales[] = $iso_code;
            }
        }
        
        $debug = $config->get('debug') ?: false;
        $current_locale = substr($language_manager->getCurrentLanguage()->getId(), 0, 2);

        // Get domain configuration - all domains in the order are available
        $domain_order = $config->get('domain_order') ?: [
            'gbfTargets',
            'nationalTargets7',
            'countries',
            'subjects',
            'sdgs',
        ];

        // Calculate auto-add values for initial render
        $auto_add_values = [];
        if ($is_biosafety && $is_new_entity) {
            // Collect all existing values from all field items
            $existing_values = [];
            foreach ($items as $item) {
                if (!empty($item->value)) {
                    $item_values = array_filter(array_map('trim', explode(',', $item->value)));
                    $existing_values = array_merge($existing_values, $item_values);
                }
            }
            $existing_values = array_unique($existing_values);
            
            // Add GBF Target 17 if not already present and auto-add is enabled.
            if (!$disable_auto_gbf17 && !in_array('GBF-TARGET-17', $existing_values)) {
                $auto_add_values[] = 'GBF-TARGET-17';
            }
            
            // Add countries if not disabled and not already present
            // Only auto-add if there is exactly one country
            if (!$disable_auto_countries && !empty($countries) && count($countries) === 1) {
                $country = reset($countries);
                if (!empty($country) && !in_array($country, $existing_values)) {
                    $auto_add_values[] = $country;
                }
            }
        }

        $classes = ['edit-scbd_field-thesaurus'];
        if (!$debug) {
            $classes[] = 'hide';
        }

        $element['value'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Tags'),
            '#default_value' => $value,
            '#size' => 4000000000,
            '#maxlength' => 4000000000,
            '#multiple' => false,
            '#suffix' => '<div id="scbd-field-thesaurus-' . $field_name . '" style="min-height: 50px; border: 1px dashed #ccc; padding: 10px; margin: 5px 0;"></div>',
            '#attributes' => ['class' => $classes],
            '#attached' => [
                'drupalSettings' => [
                    'scbd_field' => [
                        'element_title' => $field_name,
                        'full_field_name' => $full_field_name,
                        'element_description' => $element['#description'] ?? '',
                        'initial_value' => $value,
                        'initial_value2' => $value2,
                        'countries' => $countries,
                        'locales' => $locales,
                        'locale' => $current_locale,
                        'domains' => $domain_order,
                        'debug' => $debug,
                        'auto_add_values' => $auto_add_values,
                    ],
                ],
                'library' => [
                    'scbd_field/thesaurus',
                ],
            ],
        ];

      // Add a second field for value2.
        $classes2 = ['edit-scbd_field-thesaurus-additional'];
        if (!$debug) {
            $classes2[] = 'hide';
        }

        $element['value2'] = [
        '#type' => 'textfield',
        '#title' => $this->t(' '),
        '#default_value' => $value2,
        '#size' => 4000000000,
        '#maxlength' => 4000000000,
        '#multiple' => false,
        '#attributes' => ['class' => $classes2],
        ];

        return $element;
    }
}
