/**
 * @file
 * Javascript for SCBD Field thesaurus widget functionality.
 */
(function ($) {

  'use strict';

  /**
   * Drupal behavior for SCBD thesaurus widget initialization.
   * @type {Object}
   */
  Drupal.behaviors.scbd_thesaurus_widget = {
    /**
     * Attach behavior to initialize the thesaurus widget.
     * @param {Element} context - The context element
     * @param {Object} settings - Drupal settings object
     */
    attach: function (context, settings) {
     
  // Content type change handling removed with additional JS cleanup.
      const countries = settings.scbd_field?.countries || ['be'];
      const locale    = settings.scbd_field?.locale    || 'en';
      const locales   = settings.scbd_field?.locales   || ['en'];

      const name         = settings?.element_title.toLowerCase();
      const description  = settings?.element_description;

  // Removed: typePlacementOnChange

      const mountElement = document.querySelector(`#scbd-field-thesaurus-${name}`);

      if(!mountElement) return;
      if(!name)         return;

      if(!mountElement.__vue_app__) mountVueApp({ name, description, countries, locale, locales });


      hideTextFormat();
    }
  };
})(jQuery);

/**
 * Mount the Vue.js application for the thesaurus widget.
 * @param {Object} params - Configuration parameters
 * @param {string} params.name - Element name for mounting
 * @param {string} params.description - Element description
 * @param {Array} params.countries - Array of country codes
 * @param {string} params.locale - Current locale
 * @param {Array} params.locales - Available locales
 */
function mountVueApp({ name, description, countries, locale, locales }){
  const { createApp } = Vue;
  const   domains     = ['gbfTargets', 'nationalTargets7', 'countries', 'subjects','sdgs'];
  const   App         = ScbdDrupalScbdFieldJs.default;
  const   anApp       = createApp(App, { name, description, countries, locale, locales, domains});

  anApp.mount(`#scbd-field-thesaurus-${name}`)
}

/**
 * Hide text format elements from the body field.
 * Hides the format selection label and help link.
 */
function hideTextFormat(){
  const label = document.querySelector('label[for="edit-body-0-format--2"]');
  const helpLink = document.querySelector('#edit-body-0-format-help-about');

  if(label) 
    label.style.display = 'none';
  if(helpLink)
    helpLink.style.display = 'none';
}

/**
 * Handle content type field changes by updating field visibility and Vue apps.
 * @param {string} locale - Current locale
 * @param {string} name - Element name
 */
// Removed: typePlacementOnChange/handleContentTypeChange and helpers no longer used.

// Expose test hooks in Node/Jest without affecting browser usage.
if (typeof module !== 'undefined' && module.exports) {
  module.exports = {
    mountVueApp,
    hideTextFormat,
  };
}