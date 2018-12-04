<?php

namespace Drupal\paypal_payments\Services;


use Drupal\Core\Database\Database;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Class paypalSettings
 *
 * Settings for paypal api ie environment, clientID and clientSecret
 * @package Drupal\paypal_payments\Services
 */
class paypalSettings {

  /**
   * paypalSettings constructor.
   *
   * @todo  classes here we might need/ better test
   */
  public function __construct() {

  }

  /**
   * saves or updates the credentials from the paypal settings form
   */
  public function setPaypalCredentials(FormStateInterface $form_state){

    /**
     * if table has any values, update instead
     */
    if ($this->ifCredentialsAreSet()) {
      $this->updatePaypalCredentials($form_state);
    }

    else {
      $insert = Database::getConnection()->insert('paypal_payments_settings');
      $insert->fields(['environment', 'client_id', 'client_secret'])
        ->values([
          'environment' => $this->getEnvironment($form_state),
          'client_id' => $form_state->getValue('client_id'),
          'client_secret' =>  $form_state->getValue('client_secret'),
        ])
        ->execute();

      \Drupal::messenger()->addMessage('Your PayPal settings have been 
      saved, you may update changes later ');
    }

  }

  /**
   * gets the paypal credentials saved in the db for use with our
   * api calls
   * Use this with in your calls
   */
  protected function getPaypalCredentials(){

    $results = $this->querySettingsTable();

    /**
     * Go get your credentials so we can make the calls
     */
    //return !empty($results->fetchCol());
    return $results->fetchAll(\PDO::FETCH_ASSOC);

  }

  /**
   * Updates the credentials already set in setPaypalCredentials
   * since its meant to run only once..call this every other time
   * a user updates the settings form with new info
   */
  protected function updatePaypalCredentials(FormStateInterface $form_state){
    //TODO:: work on updating shit now..what what update query
    Database::getConnection()->update('paypal_payments_settings')
      ->fields([
        'environment' =>$this->getEnvironment($form_state),
        'client_id' => $form_state->getValue('client_id'),
        'client_secret' =>$form_state->getValue('client_secret'),
      ])
      ->execute();
    \Drupal::messenger()->addMessage('Your PayPal Settings have been UPDATED');
  }

  /**
   * Check if Credentials are set, if TRUE don't write to the database
   * instead update
   */
  protected function ifCredentialsAreSet(){
    $results = $this->querySettingsTable();

    return !empty($results->fetchAll());
  }

  /**
   * Just gets the set environment if none is set live/sandbox
   * from the form input
   * throw for no checkbox is checked
   */
  protected function getEnvironment(FormStateInterface $form_state){
    $env = $form_state->getValue('environment');
    foreach (array_filter($env) as $theEnvironment) {
      return $theEnvironment;
    }

    /**
     * if environment not selected, throw an exception
     * TODO:: move this to form validate instead
     */

    return new Exception('Please set you paypal payments environment');
  }

  /**
   * Helper function for use in getPaypalCredentials()
   *  and ifCredentialsAreSet()
   * @return \Drupal\Core\Database\StatementInterface|null
   */
  protected function querySettingsTable(){
    $select = Database::getConnection()->select('paypal_payments_settings', 'ps');
    $select->fields('ps', ['environment', 'client_id', 'client_secret']);
    $results = $select->execute();

    return $results;
  }

  /**
   * Raw settings array from database
   * @return mixed
   */
  protected function getSetting(){
    $settings = $this->getPaypalCredentials();
    foreach ($settings as $key => $setting){
      return $setting;

    }
    //return $setting;
  }

  /**
   * Get the environment saved in the database
   */
  public function getSetEnvironment(){
    $environment = $this->getSetting();
    return $environment['environment'];
  }

  /**
   * Get the client ID from database
   */
  public function getClientId(){
    $clientID = $this->getSetting();
    return $clientID['client_id'];

  }

  /**
   * Get the client secret from database
   */
  public function getClientSecret(){
    $clientSecret = $this->getSetting();
    return $clientSecret['client_secret'];
  }

  /**
   * Since every paypal api request will require redirects
   * create a method to handle that too in relation to
   * related node
   *
   * @var  $node \Drupal\node\Entity\Node
   */
  public function redirectToOwningNode(Node $node, FormStateInterface $form_state){

    $entity_id = $node->id();
    $node_bundle = $node->bundle();
    $node_label = $node->label();

    $node_route = \Drupal::routeMatch()->getRouteName();

    $redirect = $form_state->setRedirect($node_route, ['node'=> $entity_id ]);

    return $redirect;

  }

}