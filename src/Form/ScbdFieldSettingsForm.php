<?php

namespace Drupal\scbd_field\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure SCBD Field settings.
 */
class ScbdFieldSettingsForm extends ConfigFormBase
{
  /**
   * {@inheritdoc}
   */
    protected function getEditableConfigNames()
    {
        return ['scbd_field.settings'];
    }

  /**
   * {@inheritdoc}
   */
    public function getFormId()
    {
        return 'scbd_field_settings_form';
    }

  /**
   * {@inheritdoc}
   */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('scbd_field.settings');

        $form['countries'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Countries'),
        '#description' => $this->t('Enter country codes, one per line.'),
        '#default_value' => implode("\n", $config->get('countries') ?: []),
        ];

        $form['locales'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Locales'),
        '#description' => $this->t('Enter locale codes, one per line.'),
        '#default_value' => implode("\n", $config->get('locales') ?: []),
        ];

        return parent::buildForm($form, $form_state);
    }

  /**
   * {@inheritdoc}
   */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $countries = array_filter(array_map('trim', explode("\n", $form_state->getValue('countries'))));
        $locales = array_filter(array_map('trim', explode("\n", $form_state->getValue('locales'))));

        $this->config('scbd_field.settings')
        ->set('countries', $countries)
        ->set('locales', $locales)
        ->save();

        parent::submitForm($form, $form_state);
    }
}
