<?php

namespace Drupal\paypal_payments\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PayPalPaymentsSettingsForm.
 *
 * Store the paypal credentials required to make the api calls
 */
class PayPalPaymentsConfigForm extends ConfigFormBase {

  /**
   * Gets the configuration names that will be editable.
   *
   * @return array
   *   An array of configuration object names that are editable if called in
   *   conjunction with the trait's config() method.
   */
  protected function getEditableConfigNames() {

    return ['paypal_payments.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paypal_payments_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('paypal_payments.settings');
    $environmentTypes = [
      'live' => 'Live',
      'sandbox' => 'Sandbox',
    ];

    /**
     * Set the currency manually,
     * TODO::we should import this and append to our field item plugin instead
     * Add more
     */
    $currency = [
      'USD' => 'USD',
      'GBP' => 'GBP',
      'AUD' => 'AUD',
      'CAD' => 'CAD',
      'EUR' => 'EUR',
      'JPY' => 'JPY'
    ];

    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client ID'),
      '#description' => $this->t('The Client ID from PayPal, you can put any value here if you have set PP_CLIENT_ID in your environment variables'),
      '#default_value' => '',#$config->get('client_id'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('The Client Secret Key From PayPal, (You can put any value here, if you have set PP_CLIENT_ID in your env variables.)'),
      '#default_value' => '',#$config->get('client_secret'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['environment'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Environment'),
      '#options' => $environmentTypes,
      '#description' => $this->t('Select either; live or sandbox(for development)'),
      #'#default_value' => [$config->get('environment')],
      '#required' => TRUE,
      '#multiple' => FALSE,
    ];
    $form['store_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Store Currency'),
      '#options' => $currency,
      '#description' => $this->t('Select the currency to use with your store'),
      '#default_value' => $config->get('store_currency'),
      '#required' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    //TODO:: check if the keys are valid
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $env = $form_state->getValue('environment');

    $config = $this->config('paypal_payments.settings');
    $config
      ->set('store_currency', $form_state->getValue('store_currency'))
      ->set('environment', reset($env))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('client_id', $form_state->getValue('client_id'))
      ->save();

    #drupal_flush_all_caches();
    parent::submitForm($form, $form_state);

  }
}
