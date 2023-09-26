/**
 * @file
 * Javascript for Field Example.
 */


(function ($) {

  'use strict';

  Drupal.behaviors.scbd_thesaurus_widget = {
    attach: function (context, settings) {
      hideDates();
      typePlacementOnChange($)

      const name         = settings?.element_title.toLowerCase();
      const description  = settings?.element_description
      const mountElement = document.querySelector(`#scbd-field-thesaurus-${name}`);

      console.log();
      if(!mountElement) throw new Error(`mount element not found: #scbd-field-thesaurus-${name}`);
      if(!name) throw new Error('element_title is required on Settings');

      if(!mountElement.__vue_app__) mountVueApp({ name, description });
      hideDates();
    }
  };
})(jQuery);

function mountVueApp({ name, description }){
  const { createApp } = Vue;
  const App = ScbdDrupalScbdFieldJs.default;
  const anApp = createApp(App, { name, description });

  anApp.mount(`#scbd-field-thesaurus-${name}`)
}

function hideDates(){
  const typePlacementInputEl = document.querySelector('#edit-field-type-placement-target-id');

  if(!typePlacementInputEl) throw new Error('typePlacementInputEl not found');

  const startDateWrapper = document.querySelector('#edit-field-start-date-wrapper');
  const endDateWrapper   = document.querySelector('#edit-field-end-date-wrapper');

  if(typePlacementInputEl.value.includes('(3)')){
    startDateWrapper.style.display = 'block';
    endDateWrapper.style.display = 'block';
  } else{
    startDateWrapper.style.display = 'none';
    endDateWrapper.style.display = 'none';
  }

}

function typePlacementOnChange($){
  const typePlacementInputEl = document.querySelector('#edit-field-type-placement-target-id');

  if(!typePlacementInputEl) throw new Error('typePlacementInputEl not found');

  $('#edit-field-type-placement-target-id').on('change', hideDates)

  $('#edit-field-type-placement-target-id').on('keydown', hideDates)

  $('#edit-field-type-placement-target-id').on('mouseout', hideDates)

}