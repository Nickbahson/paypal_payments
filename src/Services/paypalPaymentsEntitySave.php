<?php


namespace Drupal\paypal_payments\Services;


use Drupal\Component\Uuid\Php;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Custom save for paypal entities
 * TODO::work on using entity repository to write and update table 'paypal'
 * Class paypalPaymentsEntitySave
 *
 * @package Drupal\paypal_payments\Services
 */
class paypalPaymentsEntitySave {

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
  public function paypalPaymentSuccess($entity_id, $user_id, $price, $sku){
    /**
     * Get the node from the request
     * @var \Drupal\node\Entity\Node $node
     */
    $request = $this->request->getCurrentRequest();
    $node = $request->attributes->get('node');
    //$entity_id = $node->get('entity_id');//pass the entity ID when we call this on routeSub


    //$insert = Database::getConnection()->insert('paypal_payments');
    $insert = $this->database_connection->insert('paypal_payments');
    $insert->fields(['uuid','langcode', 'user_id', 'sku', 'status', 'entity_id', 'price', 'payment_status', 'created', 'changed'])
      ->values([
        'uuid' => $this->uuid->generate(),#$uuid->generate(),
        'langcode' => 'eng',#set to store or remove field totally
        'user_id' => $user_id,
        'sku' => $sku,
        'status' => 1,#TRUE for published, 0 for unpublished/refunded
        'entity_id' => $entity_id,
        'price' => $price,
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

  public function paypalPaymentsRefund(){

  }
}