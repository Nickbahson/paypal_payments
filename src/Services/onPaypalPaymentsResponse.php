<?php


namespace Drupal\paypal_payments\Services;


use Drupal\Component\Uuid\Php;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class onPaypalPaymentsResponse
 *
 * @package Drupal\paypal_payments\Services
 */
class onPaypalPaymentsResponse {

  /**
   * @var \Drupal\Component\Uuid\Php
   */
  private $uuid;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $database_connection;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  public function __construct(Connection $database_connection, RequestStack $request, Php $uuid) {
    $this->uuid = $uuid;
    $this->database_connection = $database_connection;
    $this->request = $request;
  }

  public function paypalPaymentChargeSuccess(){

  }


  public function paypalPaymentsFailed(){

  }

  public function paypalPaymentsRefund(){

  }

  /**
   * call this on paypal payment success
   */
  public function insertIntoPaypalPayments($nid, $transaction_id, $sku, $uid, $payment_status){
    $insert = $this->database_connection->insert('paypal_payments');
    $insert->fields(['sku', 'uid','nid', 'transaction_id', 'payment_status', 'created',])
      ->values([
        'sku' => $sku,
        'uid' => $uid,
        'nid' => $nid,
        'transaction_id' => $transaction_id,
        'payment_status' => $payment_status,
        'created' => time(),
      ])
      ->execute();
  }

  /**
   * call this on paypal payment success
   */
  public function insertIntoReceipts($nid, $transaction_id, $payer_email, $amount, $sale_id, $invoice_id){
    //Insert into table two
    $insert = $this->database_connection->insert('paypal_payments_receipts');
    $insert->fields(['payer_email', 'amount', 'entity_id', 'transaction_id', 'sale_id', 'invoice_id',])
      ->values([
        'entity_id' => $nid,
        'transaction_id' => $transaction_id,
        'payer_email' => $payer_email,
        'amount' => $amount,
        'sale_id' => $sale_id,
        'invoice_id' => $invoice_id,
      ])
      ->execute();
  }
}