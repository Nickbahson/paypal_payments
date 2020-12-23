<?php


namespace Drupal\paypal_payments\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use PayPalCheckoutSdk\Core\PayPalHttpClient;
use PayPalCheckoutSdk\Core\SandboxEnvironment;
use PayPalCheckoutSdk\Core\ProductionEnvironment;
use PayPalCheckoutSdk\Orders\OrdersCaptureRequest;
use PayPalHttp\HttpException;

ini_set('error_reporting', E_ALL); // or error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

class PayPalClient {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /** @var  \Drupal\Core\Entity\EntityTypeManagerInterface */
  protected $entityTypeManager;

  public function setConfig(ConfigFactoryInterface $configFactory){
    $this->configFactory = $configFactory;
  }

  public function setEntity(EntityTypeManagerInterface $entityTypeManager){
    $this->entityTypeManager = $entityTypeManager;
  }

  public function getConfigs(){
    $config = $this->configFactory->getEditable('paypal_payments.settings');
    $client_id = $config->get('client_id');
    $client_secret = $config->get('client_secret');
    $environment = $config->get('environment');
    $store_currency = $config->get('store_currency');

    return [
      'client_id' => $client_id,
      'client_secret' => $client_secret,
      'environment' => $environment,
      'currency' => $store_currency,
    ];
  }

  /**
   * Returns PayPal HTTP client instance with environment that has access
   * credentials context. Use this instance to invoke PayPal APIs, provided the
   * credentials have access.
   */
  public function client() {
    return new PayPalHttpClient($this->environment());
  }

  /**
   * Set up and return PayPal PHP SDK environment with PayPal access credentials.
   * This sample uses SandboxEnvironment. In production, use LiveEnvironment.
   */
  protected function environment() {

    //
    $config = $this->getConfigs();

    $clientId = getenv("CLIENT_ID") ?: $config['client_id'];
    $clientSecret = getenv("CLIENT_SECRET") ?: $config['client_secret'];

    if ($config['environment'] === 'sandbox') {
      return new SandboxEnvironment($clientId, $clientSecret);
    } else return new ProductionEnvironment($clientId, $clientSecret);

  }

  /**
   * @param $order_id
   * the APPROVED-ORDER-ID
   */
  public function captureOrder($order_id){

    $request = new OrdersCaptureRequest($order_id);
    $request->prefer('return=representation');
    try {
      // Call API with your client and get a response for your call
      $response = $this->client()->execute($request);

      $status_code = $response->statusCode;
      $status = $response->result->status;
      $id = $response->result->id;
      $email_address = $response->result->payer->email_address;
      $intent = $response->result->intent;
      $currency_code = $response->result->purchase_units[0]->amount->currency_code;
      $payments_id = $response->result->purchase_units[0]->payments->captures[0]->id;
      $amount = $response->result->purchase_units[0]->amount->value;

      $entity = $this->entityTypeManager->getStorage('paypal_payments');

      $values = [
        'uid' => 10,
        'payer_email' => $email_address,
        'nid' => 20,
        'amount' => $amount,
        'transaction_id' => $payments_id,
        'sale_id' => $id,
        'payment_status' => $status,
        'invoice_id' => FALSE,
        'sku' => '',
      ];


      $entity->create($values)->save();

      \Drupal::messenger()->addMessage(t('Transaction completed for %a and id : %i'));

      return $response;
    }catch (HttpException $ex) {
      //echo $ex->statusCode;
      //print_r($ex->getMessage());
      return \Drupal::messenger()->addError('Issue completing the transaction : '.$ex->getMessage());
    }

  }
}