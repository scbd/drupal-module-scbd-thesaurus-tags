<?php

namespace Drupal\scbd_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'scbd_field_thesaurus' field type.
 *
 * @FieldType(
 *   id = "scbd_field_thesaurus",
 *   label = @Translation("SCBD Thesaurus"),
 *   description = @Translation("SCBD Thesaurus field."),
 *   category = @Translation("SCBD"),
 *   module = "scbd_field",
 *   default_widget = "scbd_thesaurus_widget",
 *   cardinality = 1,
 * )
 */
class ScbdFieldThesaurus extends FieldItemBase
{
  /**
   * {@inheritdoc}
   */
    public static function schema(FieldStorageDefinitionInterface $field_definition)
    {
        return [
        'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => false,
        ],
        'value2' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => false,
        ],
        ],
        ];
    }

  /**
   * {@inheritdoc}
   */
    public function isEmpty()
    {
        $value = $this->get('value')->getValue();
        $value2 = $this->get('value2')->getValue();
        $empty1 = $value === null || $value === '';
        $empty2 = $value2 === null || $value2 === '';
        return $empty1 && $empty2;
    }

  /**
   * {@inheritdoc}
   */
    public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition)
    {
        $properties['value'] = DataDefinition::create('string')
        ->setLabel(t('Value'));

        $properties['value2'] = DataDefinition::create('string')
        ->setLabel(t('Value 2'));

        return $properties;
    }

  /**
   * {@inheritdoc}
   */
    public function preSave()
    {
        parent::preSave();
        
        // Check if biosafety mode is enabled
        $bioland_config = \Drupal::config('bioland.settings');
        $is_biosafety = $bioland_config ? (bool) $bioland_config->get('is_biosafety_land') : false;
        
        $config = \Drupal::config('scbd_field.settings');
        $disable_auto_gbf17 = $config->get('disable_auto_gbf17') ?: false;
        $disable_auto_countries = $config->get('disable_auto_countries') ?: false;
        
        if ($is_biosafety) {
            $value = $this->get('value')->getValue();
            $values = !empty($value) ? array_filter(array_map('trim', explode(',', $value))) : [];
            
            // Add Target 17 identifier if not already present (always when is_biosafety is true)
            if (!in_array('GBF-TARGET-17', $values)) {
                $values[] = 'GBF-TARGET-17';
            }
            
            // Add countries from bioland.settings.countries as tags if not disabled
            // Only add if exactly one country is configured
            if (!$disable_auto_countries) {
                $countries = $bioland_config->get('countries') ?: [];
                // Only auto-add if there is exactly one country
                if (count($countries) === 1) {
                    $country = reset($countries);
                    if (!empty($country) && !in_array($country, $values)) {
                        $values[] = $country;
                    }
                }
            }
            
            $this->set('value', implode(',', $values));
        }
    }
}
