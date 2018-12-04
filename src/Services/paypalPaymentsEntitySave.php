<?php


namespace Drupal\paypal_payments\Services;


use Drupal\Core\Database\Database;

/**
 * Custom save for paypal entities
 * TODO::work on using entity repository to write and update table 'paypal'
 * Class paypalPaymentsEntitySave
 *
 * @package Drupal\paypal_payments\Services
 */
class paypalPaymentsEntitySave {

  public function paypalPaymentSuccess(){

    $uuid = \Drupal::service('uuid');

    $insert = Database::getConnection()->insert('paypal');
    $insert->fields(['uuid','langcode', 'user_id', 'sku', 'status', 'entity_id', 'price', 'payment_status', 'created', 'changed'])
      ->values([
        'uuid' => $uuid->generate(),
        'langcode' => 'eng',
        'user_id' => 2,
        'sku' => 'sku',
        'status' => 1,
        'entity_id' => 2,
        'price' => 100,
        'payment_status' => 'Success',
        'created' => time(),
        'changed' => time(),
      ])
      ->execute();

    \Drupal::messenger()->addMessage('You have paid for this');

  }

  public function paypalPaymentChargeSuccess(){

  }


  public function paypalPaymentsFailed(){

  }
}