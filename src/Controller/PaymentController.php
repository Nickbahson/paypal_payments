<?php


namespace Drupal\paypal_payments\Controller;


use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\paypal_payments\Services\GetOrder;
use Drupal\paypal_payments\Services\PayPalClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class PaymentController extends ControllerBase {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\paypal_payments\Services\PayPalClient
   */
  private $payPalClient;

  public function __construct(ConfigFactoryInterface $configFactory, PayPalClient $payPalClient) {
    $this->configFactory = $configFactory;
    $this->payPalClient = $payPalClient;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('paypal_payments.paypal_client')
    );
  }


  public function paypalSdk(Request $request){

    $order_id = $request->get('order_id');

    $config = $this->configFactory->getEditable('paypal_payments.settings');

    $client_id = $config->get('client_id');
    $client_secret = $config->get('client_secret');
    $environment = $config->get('environment');
    $store_currency = $config->get('store_currency');

    $order = $this->payPalClient->captureOrder($order_id);



    dump($client_id);
    dump($client_secret);
    dump($environment);
    dump($store_currency);
    return [
      '#theme' => 'sdk_page',
      '#cache' => ['max-age' => 0],
      '#data' => [],
    ];
  }

}