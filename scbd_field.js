/**
 * @file
 * Javascript for Field Example.
 */

let typePlacementInputEl = undefined;
let contentTypeValue     = undefined;
let additionalFieldsEl   = undefined;
let summaryFieldEl      = undefined;
let bodyFieldEl         = undefined;


let startDateWrapper     = undefined;
let  endDateWrapper      = undefined;
let urlWrapper           = undefined;
let publishedWrapper     = undefined;
let mainLabelWrapper     = undefined;

const contentTypesWithFields = [ 3, 5, 8, 9,  12 ];//3,


const contentTypeAdditionalFields = {
  3: ['eventStatuses'], // event 
  5: ['projectStatuses', 'geoScopes'],
  8: ['orgTypes', 'govTypes'], // ministry
  9: ['ecosystemTypes'],
  12: ['documentTypes'],
};
//'orgTypes', 'govTypes', 'projectStatuses', 'geoScopes', 'documentTypes','jurisdictions'
// 3 event
// 5 project - projectStatus, geoscope
// 8 ministry - org type, 
// 3 event - status - cancelled, confirmed, postponed, tentative, other
// 12 document type - url, published

// scbd_field.settings
// [
//   "countries" => [
//     "gh"
//   ],
//   "locale" => "en",
//   "locales" => [
//     "en", "fr"
//   ],
// ]
(function ($) {

  'use strict';

  Drupal.behaviors.scbd_thesaurus_widget = {
    attach: function (context, settings) {
     
      hideFields();
      const countries = settings.scbd_field?.countries || ['gh'];
      const locale    = settings.scbd_field?.locale    || 'en';
      const locales   = settings.scbd_field?.locales   || ['en'];

      const name         = settings?.element_title.toLowerCase();
      const description  = settings?.element_description;


      
      //startAutoSummary($);

 console.log('scbd_thesaurus_widget attach', { name, description, countries, locale, locales });

      typePlacementOnChange($, { locale, name });

      const mountElement = document.querySelector(`#scbd-field-thesaurus-${name}`);

      if(!mountElement) return;
      if(!name)         return;

      if(!mountElement.__vue_app__) mountVueApp({ name, description, countries, locale, locales });


      hideFields();
      hideTextFormat();
      if(contentTypeValue && contentTypesWithFields.includes(Number(contentTypeValue)))
        mountAdditionalFieldsVueApp({ locale, name })
    }
  };
})(jQuery);

function mountVueApp({ name, description, countries, locale, locales }){
  const { createApp } = Vue;
  const   domains     = ['gbfTargets', 'nationalTargets7', 'countries', 'subjects','sdgs'];
  const   App         = ScbdDrupalScbdFieldJs.default;
  const   anApp       = createApp(App, { name, description, countries, locale, locales, domains});

  anApp.mount(`#scbd-field-thesaurus-${name}`)
}

function hideTextFormat(){
   // edit-body-0-format-help-about
// Select the label with for="edit-body-0-format--2"
  const label = document.querySelector('label[for="edit-body-0-format--2"]');
  const helpLink = document.querySelector('#edit-body-0-format-help-about');

  if(label) 
    label.style.display = 'none';
  if(helpLink)
    helpLink.style.display = 'none';



  // const textFormatWrapper = document.querySelector('#edit-body-0-format');
  
  // if(!textFormatWrapper) return;

  // textFormatWrapper.style.display = 'none';

  //edit-body-0-format
}
function hideFields(){
  getMainLabelWrapper();
  getContentTypeField(true);


  hideDates();
  hideUrl();
  hidePublished();
}

function hideUrl(){
  if(!contentTypeFieldExists()) return;

  urlWrapper = urlWrapper? urlWrapper :document.querySelector('#edit-field-url-wrapper');

  if( [13,2,4,11,12,3,8,5,4,10,9 ].includes(Number(contentTypeValue))){
    urlWrapper.style.display = 'block';
  } else{
    urlWrapper.style.display = 'none';
  }

}

function hidePublished(){
  if(!contentTypeFieldExists()) return;

  publishedWrapper = publishedWrapper? publishedWrapper : document.querySelector('#edit-field-published-wrapper');

  if( [12,2, 11].includes(Number(contentTypeValue))){
    publishedWrapper.style.display = 'block';
  } else{
    publishedWrapper.style.display = 'none';
  }

}

function hideDates(){

  if(!contentTypeFieldExists()) return;

  startDateWrapper = startDateWrapper? startDateWrapper  : document.querySelector('#edit-field-start-date-wrapper');
  endDateWrapper   = endDateWrapper?   endDateWrapper    : document.querySelector('#edit-field-end-date-wrapper');


  if([3,5].includes(Number(contentTypeValue))){
    startDateWrapper.style.display = 'block';
    endDateWrapper.style.display = 'block';
  } else{
    startDateWrapper.style.display = 'none';
    endDateWrapper.style.display = 'none';
  }
}

function typePlacementOnChange($, { locale, name }){

  if(!contentTypeFieldExists()) return false;

  $('#edit-field-type-placement').on('change', () => { hideFields(); mountAdditionalFieldsVueApp({ locale, name }); })

  $('#edit-field-type-placement').on('keydown', () => { hideFields(); mountAdditionalFieldsVueApp({ locale, name }); })

  $('#edit-field-type-placement').on('mouseout', () => { hideFields(); mountAdditionalFieldsVueApp({ locale, name }); })

  
}

function getContentTypeField(force = false){
  const loadElement = force || !typePlacementInputEl ;

  typePlacementInputEl = loadElement? document.querySelector('#edit-field-type-placement') : typePlacementInputEl ;

  if(!typePlacementInputEl) return false;

  contentTypeValue = typePlacementInputEl.value;

  return typePlacementInputEl;
}

function getSummaryField(force = false){
  const loadElement = force || !summaryFieldEl;

  summaryFieldEl = loadElement? document.querySelector('#edit-body-0-summary') : summaryFieldEl ;

  if(!summaryFieldEl) return false;

  return summaryFieldEl;
}

function getBodyField(force = false){
  const loadElement = force || !bodyFieldEl;

  bodyFieldEl = loadElement? document.querySelector('#edit-body-0-value') : bodyFieldEl ;

  return bodyFieldEl;
}

function startAutoSummary($ = jQuery){
  const start = shouldAutoSummary();

  if(!start) return false;

  $('#edit-body-0-value').on('change', updateSummaryField)

  $('#edit-body-0-value').on('keydown', updateSummaryField)

  $('#edit-body-0-value').on('mouseout', updateSummaryField)

  $('#edit-body-0-summary').on('change', removeAutoSummary($))

  console.log('============ Auto summary started');
}

function removeAutoSummary($){ 
  
  return () => {
                  $('#edit-body-0-value').off('change', updateSummaryField)

                  $('#edit-body-0-value').off('keydown', updateSummaryField)

                  $('#edit-body-0-value').off('mouseout', updateSummaryField)

                  $('#edit-body-0-summary').off('change', removeAutoSummary($))
                    console.log('============ remove AutoSummary ');
              }

}

function shouldAutoSummary($){
  const bodyEl    = getBodyField();
  const summaryEl = getSummaryField();

  if(!bodyEl || !summaryEl) return false;

  const emptyBody    = !bodyEl.value || bodyEl.value.length < 10;
  const emptySummary = !summaryEl.value || summaryEl.value.length < 10;

  if(emptyBody && !emptySummary) return false;
  if(!emptyBody && !emptySummary) return false;

  if(emptyBody && emptySummary) return true;

  //if(!emptyBody && emptySummary) 
  updateSummaryField();

  return true;
}

function updateSummaryField(){
  console.log('============ updateSummaryField called');
  const bodyEl    = getBodyField();
  const summaryEl = getSummaryField();

  console.log('============ updateSummaryField called', bodyEl.value);

  const locale    = document.querySelector('html').getAttribute('lang') || 'en';

  summaryEl.value = smartTruncate(stripHtml(bodyEl.value), 255, locale);

  summaryEl.dispatchEvent(new Event('change'));
}



function contentTypeFieldExists(){

  return typePlacementInputEl;
}

function getMainLabelWrapper(){
  mainLabelWrapper = mainLabelWrapper? mainLabelWrapper : document.querySelector('#edit-field-tags-wrapper');

  return mainLabelWrapper;
}

function createAdditionalFieldsElementMount(){

  const contentTypeField = getContentTypeField();
  
  if(!contentTypeField || !contentTypeValue) return false;

  if(additionalFieldsEl?.__vue_app__) additionalFieldsEl.__vue_app__.unmount();

  if(additionalFieldsEl){
    additionalFieldsEl.remove();
    additionalFieldsEl = undefined;
  }

  additionalFieldsEl = document.createElement('div');

  additionalFieldsEl.setAttribute('id', 'bl-additional-fields')

  const wrapperEl = getMainLabelWrapper();

  wrapperEl.insertBefore(additionalFieldsEl, wrapperEl.firstChild);

  return additionalFieldsEl;
}

function mountAdditionalFieldsVueApp({ locale, name }){
  createAdditionalFieldsElementMount();

  const domains = contentTypeAdditionalFields[contentTypeValue];

  if(!domains || !Array.isArray(domains) || domains.length === 0) return false;

  const { createApp } = Vue;
  const   App         = ScbdDrupalScbdFieldJs.default;
  const   anApp       = createApp(App, { name, description: ' ', locale, domains, isAdditionalField:true });

  anApp.mount(`#bl-additional-fields`)
}


function smartTruncate(texts,length = 512, locale='en'){
    if(!texts) return '';
    const text      = stripHtml(texts).result;
    const segmenter = new Intl.Segmenter( locale, { granularity: 'sentence' } );
    const segments  = Array.from( segmenter.segment(text), s => s.segment );

    let charCount = 0;
    let i         = 0;
    
    const sentences= [];


    for (const segment of segments) {

        if(charCount + segment.length > length)
            break;

        sentences.push(segment);
        charCount =+ segment.length;
        i++
    }

    const result      = Array.isArray(sentences.join(''))? sentences.join('')[0]: sentences.join('');
    const response    = result.length > length? result.substring(0,length): result;
    const lastIndexOf = response.includes('.')? response.lastIndexOf('.') + 1: response.length;


    return response.substring(0, lastIndexOf)
}

/**
 * Strips all HTML tags from a string using regex.
 * @param {string} input
 * @returns {string}
 */
function stripHtml(input) {
  if (!input) return '';
  return input.replace(/<[^>]*>/g, '');
}