<?php
/**
 * writes and reads the database table(s) for paypal payments receipts
 */


namespace Drupal\paypal_payments\Services;

use Drupal\Component\Uuid\Php;
use Drupal\Core\Database\Connection;
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

  /**
   * call this on refund, update relevant database tables accordingly
   */
  public function paypalPaymentsRefund(){
    #TODO:: implement refund
  }


  /**
   * get all available payments record
   *
   * @return array
   */
  public function getAllPaymentReceipts(){
    $query = $this->database_connection->select('paypal_payments_receipts', 'pr');

    // Join the two paypal payments table
    $query->join('paypal_payments', 'pp', 'pr.id = pp.id');

    // Join the users table, so we can get the user name of the payer
    // TODO::


    //Join to nodes table so we get the title
    $query->join('node_field_data', 'n', 'pr.entity_id = n.nid');

    // Select the specific fields for output

    $query->addField('n', 'title');
    $query->addField('pp', 'nid');
    $query->addField('pr', 'amount');
    $query->addField('pr', 'sale_id');

    $entries = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return $entries;
  }

  /**
   * queries the payment receipts per the loaded user in the url
   *
   * @return array
   */
  public function getReceiptsPerUser($uid){
    $query = $this->database_connection->select('paypal_payments_receipts', 'pr');

    // Join the two paypal payments table
    $query->join('paypal_payments', 'pp', 'pr.id = pp.id');

    //Set out query condition before continuing
    $query->condition('pp.uid', $uid);
    // Join the users table, so we can get the user name of the payer
    // TODO::


    //Join to nodes table so we get the title
    $query->join('node_field_data', 'n', 'pr.entity_id = n.nid');

    // Select the specific fields for output

    $query->addField('n', 'title');
    $query->addField('pp', 'nid');
    $query->addField('pr', 'amount');
    $query->addField('pr', 'sale_id');

    $entries = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);
    return $entries;
  }

}