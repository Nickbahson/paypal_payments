<?php

namespace Drupal\paypal_payments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PayPalPaymentsForm.
 */
class PayPalPaymentsForm extends FormBase {


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'paypal_payments_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    #$form['node'] = [
      #'#type' => 'entity_autocomplete',
      #'#title' => $this->t('node'),
      #'#description' => $this->t('The node/Product related to the payment id'),
      #'#weight' => '0',
    #];
    $form['price'] = [
      '#type' => 'number',
      '#title' => $this->t('Price'),
      '#description' => $this->t('The price of the product'),
      '#weight' => '0',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
