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
     * @param {HTMLElement} context
     * @param {Object} settings
     */
    attach: function (context, settings) {
      // Always log for debugging
      console.log('SCBD Field Widget - Behavior attach called', {
        contextType: context.nodeType,
        hasSettings: !!settings,
        hasScbdField: !!settings?.scbd_field,
        settingsKeys: Object.keys(settings || {}),
        windowDrupalSettings: typeof window.drupalSettings !== 'undefined'
      });

      // Find all mount elements within the current context
      const mountElements = context.querySelectorAll ? 
        context.querySelectorAll('[id^="scbd-field-thesaurus-"]') : 
        document.querySelectorAll('[id^="scbd-field-thesaurus-"]');
      
      console.log('SCBD Field Widget - Mount elements found:', mountElements.length, Array.from(mountElements).map(el => el.id));
      
      if (!mountElements.length) {
        console.log('SCBD Field Widget - No mount elements found, exiting');
        return;
      }

      // Try to get settings from the passed argument first, then fall back to window.drupalSettings
      let widgetSettings = settings?.scbd_field;
      
      if (!widgetSettings && typeof window.drupalSettings !== 'undefined') {
        console.log('SCBD Field Widget - Settings not in behavior param, checking window.drupalSettings');
        widgetSettings = window.drupalSettings?.scbd_field;
      }
      
      if (!widgetSettings) {
        console.error('SCBD Field Widget - No settings.scbd_field found anywhere!', {
          settingsKeys: Object.keys(settings || {}),
          windowDrupalSettingsKeys: typeof window.drupalSettings !== 'undefined' ? Object.keys(window.drupalSettings) : 'window.drupalSettings not defined'
        });
        return;
      }
      
      console.log('SCBD Field Widget - Found widgetSettings:', widgetSettings);
      
      const countries = widgetSettings?.countries || ['be'];
      const locale = widgetSettings?.locale || 'en';
      const locales = widgetSettings?.locales || ['en'];
      const domains = widgetSettings?.domains || ['gbfTargets', 'nationalTargets7', 'countries', 'subjects', 'sdgs'];
      const name = widgetSettings?.element_title ? widgetSettings.element_title.toLowerCase() : undefined;
      const fullFieldName = widgetSettings?.full_field_name;
      const description = widgetSettings?.element_description;
      const debug = widgetSettings?.debug || false;
      const initialValue = widgetSettings?.initial_value || '';
      const initialValue2 = widgetSettings?.initial_value2 || '';
      const autoAddValues = widgetSettings?.auto_add_values || [];

      console.log('SCBD Field Widget - Parsed settings:', {
        countries,
        locale,
        locales,
        domains,
        name,
        fullFieldName,
        description,
        debug,
        initialValue,
        initialValue2,
        autoAddValues,
        mountElementsFound: mountElements.length
      });

      if (!name) {
        console.error('SCBD Field Widget - No element_title found in settings', widgetSettings);
        return;
      }

      const mountSelector = `#scbd-field-thesaurus-${name}`;
      const mountElement = document.querySelector(mountSelector);
      
      console.log('SCBD Field Widget - Looking for mount element:', mountSelector, 'found:', !!mountElement);
      
      if (!mountElement) {
        console.error('SCBD Field Widget - Mount element not found:', mountSelector);
        return;
      }
      
      // Skip if already mounted
      if (mountElement.__vue_app__) {
        console.log('SCBD Field Widget - Already mounted, skipping');
        return;
      }

      console.log('SCBD Field Widget - About to mount Vue app');
      mountVueApp({ name, fullFieldName, description, countries, locale, locales, domains, initialValue, initialValue2, autoAddValues });
      
      // Mark as mounted to prevent re-initialization
      mountElement.__vue_app__ = true;

      hideTextFormat();
    }
  };
})(jQuery);

/**
 * Mount the Vue.js application for the thesaurus widget.
 * @param {Object} params configuration
 */
function mountVueApp ({ name, fullFieldName, description, countries, locale, locales, domains, initialValue, initialValue2, autoAddValues }) {
  // Check if Vue is available
  if (typeof Vue === 'undefined') {
    console.error('SCBD Field Widget - Vue is not loaded');
    return;
  }
  
  // Check if component bundle is available
  if (typeof ScbdDrupalScbdFieldJs === 'undefined' || !ScbdDrupalScbdFieldJs.default) {
    console.error('SCBD Field Widget - Component bundle (ScbdDrupalScbdFieldJs) is not loaded');
    return;
  }
  
  const { createApp } = Vue; // global Vue
  const App = ScbdDrupalScbdFieldJs.default; // global component bundle
  
  const mountSelector = `#scbd-field-thesaurus-${name}`;
  const mountElement = document.querySelector(mountSelector);
  
  if (!mountElement) {
    console.warn(`SCBD Field Widget - Mount element not found: ${mountSelector}`);
    return;
  }
  
  // Merge auto-add values with initial values
  let mergedValue = initialValue;
  if (autoAddValues && autoAddValues.length > 0) {
    // Parse existing values
    const existingValues = initialValue ? initialValue.split(',').map(v => v.trim()).filter(Boolean) : [];
    
    // Add auto-add values that don't already exist
    const valuesToAdd = autoAddValues.filter(v => !existingValues.includes(v));
    
    if (valuesToAdd.length > 0) {
      const allValues = [...existingValues, ...valuesToAdd];
      mergedValue = allValues.join(',');
      console.log('SCBD Field Widget - Auto-added values:', valuesToAdd, 'Merged value:', mergedValue);
    }
  }

  // Ensure the underlying hidden text field reflects the merged value so that
  // it is saved correctly even if the Vue component does not immediately
  // sync the value on mount.
  if (fullFieldName && mergedValue) {
    // Example: fullFieldName = "field_tags" -> data-drupal-selector
    // "edit-field-tags-0-value" on node forms.
    const sanitizedFieldName = fullFieldName.replace(/_/g, '-');
    const hiddenSelector = `[data-drupal-selector="edit-${sanitizedFieldName}-0-value"]`;
    const hiddenField = document.querySelector(hiddenSelector);

    if (hiddenField) {
      hiddenField.value = mergedValue;
      console.log('SCBD Field Widget - Synced hidden field value:', {
        selector: hiddenSelector,
        value: mergedValue
      });
    } else {
      console.warn('SCBD Field Widget - Hidden field not found for selector:', hiddenSelector);
    }
  }
  
  // Mount single app with all domains in their configured order
  const anApp = createApp(App, { 
    name, 
    fullFieldName,
    description, 
    countries, 
    locale, 
    locales, 
    domains,
    initialValue: mergedValue,
    initialValue2,
    isAdditionalField: false,
    additionalFieldName: name // Pass same name so Vue component can find value2 field
  });
  
  // Debug log before mounting
  console.log('SCBD Field Widget - Mounting Vue app:', {
    mountSelector,
    name,
    fullFieldName,
    initialValue: mergedValue,
    initialValue2,
    domains,
    countries,
    locale,
    locales,
    autoAddValues
  });
  
  try {
    anApp.mount(mountSelector);
    console.log('SCBD Field Widget - Successfully mounted');
  } catch (error) {
    console.error('SCBD Field Widget - Error mounting Vue app:', error);
  }
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