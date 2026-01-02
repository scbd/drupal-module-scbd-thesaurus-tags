/**
 * @file
 * Settings form behavior for SCBD Field configuration.
 */

(function ($, Drupal) {
  'use strict';

  Drupal.behaviors.scbdFieldSettingsForm = {
    attach: function (context, settings) {
      const scbdSettings = settings.scbd_field_settings || {};
      const isBiosafety = scbdSettings.is_biosafety || false;
      const hasSavedOrder = scbdSettings.has_saved_order || false;
      const biosafetyDefaults = scbdSettings.biosafety_defaults || [];

      // Only show popup if in biosafety mode and no saved order exists
      if (isBiosafety && !hasSavedOrder && biosafetyDefaults.length > 0) {
        const $form = $('#scbd-field-settings-form', context);
        
        if ($form.length && !$form.data('biosafety-popup-shown')) {
          $form.data('biosafety-popup-shown', true);
          
          // Show confirmation dialog
          if (confirm(Drupal.t('Biosafety mode detected. Would you like to apply the biosafety domain defaults?\n\nThis will set the domain order to:\n@defaults', {
            '@defaults': biosafetyDefaults.join(', ')
          }))) {
            // User clicked "OK" - apply biosafety defaults
            const $textarea = $('#edit-domain-order', $form);
            if ($textarea.length) {
              $textarea.val(biosafetyDefaults.join('\n'));
            }
          }
        }
      }
    }
  };

})(jQuery, Drupal);
