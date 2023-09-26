<?php

namespace Drupal\scbd_field\Controller;

use Drupal\scbd_field\Utility\DescriptionTemplateTrait;

/**
 * Controller for field example description page.
 *
 * This class uses the DescriptionTemplateTrait to display text we put in the
 * templates/description.html.twig file.
 */
class ScbdFieldController {

  use DescriptionTemplateTrait;
  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'scbd_field';
  }

}
