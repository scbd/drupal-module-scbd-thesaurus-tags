/** @jest-environment jsdom */

// Provide the globals expected by scbd_field.js when it runs in the browser.
global.jQuery = {};
global.Drupal = { behaviors: {} };

const { mountVueApp, hideTextFormat } = require('../../scbd_field.js');

describe('scbd_field.js', () => {
  test('hideTextFormat hides label and help link when present', () => {
    document.body.innerHTML = `
      <label for="edit-body-0-format--2">Format</label>
      <div id="edit-body-0-format-help-about">Help</div>
    `;
    hideTextFormat();
    const label = document.querySelector('label[for="edit-body-0-format--2"]');
    const help = document.querySelector('#edit-body-0-format-help-about');
    expect(label.style.display).toBe('none');
    expect(help.style.display).toBe('none');
  });

  test('mountVueApp sets up Vue app mount invocation', () => {
    // Minimal stubs for Vue & component object.
  global.Vue = { createApp: jest.fn((_Comp, _props) => ({ mount: jest.fn() })) };
    global.ScbdDrupalScbdFieldJs = { default: {} };

  document.body.innerHTML = '<div id="scbd-field-thesaurus-tags"></div>';

    expect(() => mountVueApp({
      name: 'tags',
      description: 'desc',
      countries: ['be'],
      locale: 'en',
      locales: ['en'],
    })).not.toThrow();

    expect(Vue.createApp).toHaveBeenCalled();
  });
});
