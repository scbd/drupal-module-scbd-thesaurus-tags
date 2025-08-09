/**
 * @file
 * Javascript for SCBD Field thesaurus widget functionality.
 */

// IIFE to register Drupal behavior
(function (_$) {
  'use strict';

  Drupal.behaviors.scbd_thesaurus_widget = {
    /**
     * Initialize thesaurus widget.
     * @param {HTMLElement} _context
     * @param {Object} settings
     */
    attach: function (_context, settings) {
      const countries = settings?.scbd_field?.countries || ['be'];
      const locale = settings?.scbd_field?.locale || 'en';
      const locales = settings?.scbd_field?.locales || ['en'];
      const name = settings?.element_title ? settings.element_title.toLowerCase() : undefined;
      const description = settings?.element_description;

      if (!name) return;
      const mountElement = document.querySelector(`#scbd-field-thesaurus-${name}`);
      if (!mountElement) return;

      if (!mountElement.__vue_app__) {
        mountVueApp({ name, description, countries, locale, locales });
      }

      hideTextFormat();
    }
  };
})(jQuery);

/**
 * Mount the Vue.js application for the thesaurus widget.
 * @param {Object} params configuration
 */
function mountVueApp ({ name, description, countries, locale, locales }) {
  const { createApp } = Vue; // global Vue
  const domains = ['gbfTargets', 'nationalTargets7', 'countries', 'subjects', 'sdgs'];
  const App = ScbdDrupalScbdFieldJs.default; // global component bundle
  const anApp = createApp(App, { name, description, countries, locale, locales, domains });
  anApp.mount(`#scbd-field-thesaurus-${name}`);
}

/**
 * Hide text format label & help link.
 */
function hideTextFormat () {
  const label = document.querySelector('label[for="edit-body-0-format--2"]');
  const helpLink = document.querySelector('#edit-body-0-format-help-about');
  if (label) label.style.display = 'none';
  if (helpLink) helpLink.style.display = 'none';
}

// Provide CommonJS exports for Jest (ignored in browser)
if (typeof module !== 'undefined' && module.exports) {
  module.exports = { mountVueApp, hideTextFormat };
}