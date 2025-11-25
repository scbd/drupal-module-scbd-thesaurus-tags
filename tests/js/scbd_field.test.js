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
    
    // Mock console.log to suppress debug output in tests
    global.console.log = jest.fn();

    document.body.innerHTML = '<div id="scbd-field-thesaurus-tags"></div>';

    expect(() => mountVueApp({
      name: 'tags',
      fullFieldName: 'field_tags',
      description: 'desc',
      countries: ['be'],
      locale: 'en',
      locales: ['en'],
      domains: ['gbfTargets', 'countries'],
      initialValue: '',
      initialValue2: '',
    })).not.toThrow();

    expect(Vue.createApp).toHaveBeenCalled();
  });

  test('mountVueApp handles all domains including biosafety', () => {
    global.Vue = { createApp: jest.fn((_Comp, _props) => ({ mount: jest.fn() })) };
    global.ScbdDrupalScbdFieldJs = { default: {} };
    global.console.log = jest.fn();

    document.body.innerHTML = '<div id="scbd-field-thesaurus-tags"></div>';

    mountVueApp({
      name: 'tags',
      fullFieldName: 'field_tags',
      description: 'desc',
      countries: ['be'],
      locale: 'en',
      locales: ['en'],
      domains: ['gbfTargets', 'bchSubjects', 'bchSubjectGroups'],
      initialValue: '123,456',
      initialValue2: '789',
    });

    // Should be called once with all domains passed to Vue component
    expect(Vue.createApp).toHaveBeenCalledTimes(1);
    
    // Should pass all domains to the Vue component
    expect(Vue.createApp).toHaveBeenCalledWith({}, expect.objectContaining({
      name: 'tags',
      fullFieldName: 'field_tags',
      domains: ['gbfTargets', 'bchSubjects', 'bchSubjectGroups'],
      initialValue: '123,456',
      initialValue2: '789',
      isAdditionalField: false,
      additionalFieldName: 'tags'
    }));
  });
});
