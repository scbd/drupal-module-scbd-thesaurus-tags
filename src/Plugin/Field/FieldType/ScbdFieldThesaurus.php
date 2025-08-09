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
 *   cardinality = -1,
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
}
