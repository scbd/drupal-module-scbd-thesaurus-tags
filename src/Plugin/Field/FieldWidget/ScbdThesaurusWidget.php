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
class ScbdThesaurusWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    //    $element['#title'] = $element['#title'] ?? $this->t('Tags');

    $value = $items[$delta]->value ?? '';
    $field_name = str_replace('field_','',strtolower($this->fieldDefinition->getName()));
    $element['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Tags'),
      '#default_value' => $value,
      '#size' => 4000000000,
      '#maxlength' => 4000000000,
      '#multiple' => FALSE,
      '#suffix' => '<div id="scbd-field-thesaurus-'.$field_name.'"></div>',
      '#attributes' => ['class' => ['edit-scbd_field-thesaurus', 'hide']],
      '#attached' => [
        'drupalSettings' => ['test' => $element['#title'],'element_title' => $field_name , 'element_description' => $element['#description']],
        'library' => [
          'scbd_field/thesaurus'
        ],
      ],
    ];
    return $element;
  }

}
