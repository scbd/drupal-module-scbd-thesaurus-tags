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
     * Get available domain options.
     *
     * @return array
     *   Array of domain options keyed by domain ID.
     */
    protected function getDomainOptions()
    {
        return [
            'gbfTargets' => $this->t('Global Biodiversity Framework Targets'),
            'nationalTargets7' => $this->t('National Biodiversity Targets (filtered by country)'),
            'sdgs' => $this->t('Sustainable Development Goals'),
            'countries' => $this->t('Countries'),
            'subjects' => $this->t('Thematic Areas/Subjects'),
            'bchSubjects' => $this->t('Biosafety Thematic Areas'),
            'bchSubjectGroups' => $this->t('Biosafety Thematic Area Groups'),
            'regions' => $this->t('Geographic Regions'),
            'ecosystemTypes' => $this->t('Ecosystem Types'),
        ];
    }

    /**
     * Get default domain order for standard mode.
     *
     * @return array
     *   Array of default domain IDs.
     */
    protected function getDefaultDomainOrder()
    {
        return [
            'gbfTargets',
            'nationalTargets7',
            'countries',
            'subjects',
            'sdgs',
        ];
    }

    /**
     * Get domain reference list as formatted text.
     *
     * @return string
     *   Formatted text listing all available domain keys and names.
     */
    protected function getDomainReferenceText()
    {
        $domains = $this->getDomainOptions();
        $lines = [];
        foreach ($domains as $key => $name) {
            $lines[] = sprintf('%s: %s', $key, $name);
        }
        return implode("\n", $lines);
    }

    /**
     * Get default domain order for biosafety mode.
     *
     * @return array
     *   Array of biosafety domain IDs.
     */
    protected function getBiosafetyDefaultDomains()
    {
        return [
            'bchSubjectGroups',
            'gbfTargets',
            'nationalTargets7',
            'countries',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('scbd_field.settings');
        $bioland_config = \Drupal::config('bioland.settings');
        $is_biosafety = $bioland_config ? (bool) $bioland_config->get('is_biosafety_land') : false;

        $form['debug'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Debug mode'),
            '#description' => $this->t('Show the hidden input fields for debugging purposes.'),
            '#default_value' => $config->get('debug') ?: false,
        ];

        $form['auto_settings'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Automatic Value Settings'),
            '#description' => $this->t('Configure automatic population of field values.'),
        ];

        $form['auto_settings']['disable_auto_gbf17'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Disable auto-add GBF Target 17'),
            '#description' => $this->t(
                'When unchecked (default), GBF Target 17 will automatically be added on biosafety sites.'
            ),
            '#default_value' => $config->get('disable_auto_gbf17') ?: false,
        ];

        $form['auto_settings']['disable_auto_countries'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Disable auto-add countries'),
            '#description' => $this->t(
                'When unchecked (default), countries from bioland.settings will automatically be added to the field.'
            ),
            '#default_value' => $config->get('disable_auto_countries') ?: false,
        ];

        $form['domains'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Domain Configuration'),
            '#description' => $this->t('All domains are available. Configure their display order below.'),
        ];

        $form['domains']['domain_reference'] = [
            '#type' => 'item',
            '#title' => $this->t('Available Domains'),
            '#markup' => '<pre>' . htmlspecialchars($this->getDomainReferenceText()) . '</pre>',
            '#description' => $this->t('Reference list of all domain keys and their display names.'),
        ];

        $domain_order = $config->get('domain_order') ?: $this->getDefaultDomainOrder();

        $form['domains']['domain_order'] = [
            '#type' => 'textarea',
            '#title' => $this->t('Domain Order'),
            '#default_value' => implode("\n", $domain_order),
            '#description' => $this->t(
                'Enter one domain ID per line to specify display order. All listed domains will be available.'
            ),
            '#rows' => 10,
        ];

        // Attach JavaScript to handle biosafety mode popup
        $form['#attached']['library'][] = 'scbd_field/settings_form';
        $form['#attached']['drupalSettings']['scbd_field_settings'] = [
            'is_biosafety' => $is_biosafety,
            'has_saved_order' => !empty($config->get('domain_order')),
            'biosafety_defaults' => $this->getBiosafetyDefaultDomains(),
        ];

        return parent::buildForm($form, $form_state);
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        $available_domains = array_keys($this->getDomainOptions());

        // Validate standard domain order
        $domain_order = array_filter(array_map('trim', explode("\n", $form_state->getValue('domain_order'))));
        foreach ($domain_order as $domain) {
            if (!in_array($domain, $available_domains)) {
                $form_state->setErrorByName('domain_order', $this->t('Invalid domain ID: @domain', ['@domain' => $domain]));
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $debug = (bool) $form_state->getValue('debug');
        $disable_auto_gbf17 = (bool) $form_state->getValue('disable_auto_gbf17');
        $disable_auto_countries = (bool) $form_state->getValue('disable_auto_countries');

        // Parse domain order
        $domain_order = array_filter(array_map('trim', explode("\n", $form_state->getValue('domain_order'))));

        $this->config('scbd_field.settings')
            ->set('debug', $debug)
            ->set('disable_auto_gbf17', $disable_auto_gbf17)
            ->set('disable_auto_countries', $disable_auto_countries)
            ->set('domain_order', array_values($domain_order))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
