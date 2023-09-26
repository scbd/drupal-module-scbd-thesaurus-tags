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
 *   default_formatter = "scbd_field_simple_text",
 *   cardinality = 1,
 * )
 */
class ScbdFieldThesaurus extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'text',
          'size' => 'big',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Hex value'));

    return $properties;
  }

}
