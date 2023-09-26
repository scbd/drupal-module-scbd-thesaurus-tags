<?php

namespace Drupal\scbd_field\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Plugin implementation of the 'scbd_field_simple_text' formatter.
 *
 * @FieldFormatter(
 *   id = "scbd_field_simple_text",
 *   module = "scbd_field",
 *   label = @Translation("Simple text-based formatter"),
 *   field_types = {
 *     "scbd_field_thesaurus"
 *   }
 * )
 */
class SimpleTextFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        // We create a render array to produce the desired markup,
        // "<p style="color: #hexcolor">The color code ... #hexcolor</p>".
        // See theme_html_tag().
        '#type' => 'html_tag',
        '#tag' => 'p',
        '#attributes' => [
          'style' => 'color: ' . $item->value,
        ],
        '#value' => $this->t('The color code in this field is @code', ['@code' => $item->value]),
      ];
    }

    return $elements;
  }

}
