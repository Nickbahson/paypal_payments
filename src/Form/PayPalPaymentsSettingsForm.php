<?php

namespace Drupal\paypal_payments\Form;

use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paypal_payments\Services\paypalSettings;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PayPalPaymentsSettingsForm.
 *
 * Store the paypal credentials required to make the api calls
 */
class PayPalPaymentsSettingsForm extends FormBase {

  /**
   * @var \Drupal\paypal_payments\Services\paypalSettings
   */
  private $paypal_settings;

  public function __construct(paypalSettings $paypal_settings) {
    $this->paypal_settings = $paypal_settings;
  }


  public static function create(ContainerInterface $container) {
    return new static(
      // Load our paypal_payments_settings.
      $container->get('paypal_payments.settings')
    );
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
      '#description' => $this->t('The Client ID from PayPal'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Secret'),
      '#description' => $this->t('The Client Secret Key From PayPal'),
      '#maxlength' => 128,
      '#size' => 64,
      '#required' => TRUE,
    ];
    $form['environment'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Environment'),
      '#options' => $environmentTypes,
      '#description' => $this->t('Select either; live or sandbox(for development)'),
      '#required' => TRUE,
    ];
    $form['store_currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Store Currency'),
      '#options' => $currency,
      '#description' => $this->t('Select the currency to use with your store'),
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

    $this->paypal_settings->setPaypalCredentials($form_state);

  }
}
