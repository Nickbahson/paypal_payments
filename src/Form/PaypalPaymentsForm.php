<?php
/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 12/14/2018
 * Time: 6:15 PM
 */

namespace Drupal\paypal_payments\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Path\AliasManager;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\paypal_payments\Services\paypalSettings;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PaypalPaymentsForm extends FormBase {

  /**
   * @var \Drupal\paypal_payments\Services\paypalSettings
   */
  private $paypal_settings;

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entity_field_manager;

  /**
   * @var \Drupal\Core\Path\AliasManager
   */
  private $aliasManager;

  public function __construct(paypalSettings $paypal_settings, EntityFieldManager $entity_field_manager,
                              AliasManager $aliasManager) {
    $this->paypal_settings = $paypal_settings;
    $this->entity_field_manager = $entity_field_manager;
    $this->aliasManager = $aliasManager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('paypal_payments.settings'),
      $container->get('entity_field.manager'),
      $container->get('path.alias_manager')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * The returned ID should be a unique string that can be a valid PHP function
   * name, since it's used in hook implementation names such as
   * hook_form_FORM_ID_alter().
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {

    return 'paypal_payments_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pay With Paypal'),
    ];

    return $form;
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $price = $this->getPrice($this->getPaypalFieldItem());
    $currency = $this->paypal_settings->getSetStoreCurrency();
    if (!$price) {
      die('Price not set');
    }

    $this->paypalRequest($price, $currency, $form_state);
  }

  protected function paypalRequest($itemPrice, $currency, FormStateInterface $form_state){
    /**
     * Paypal request , should be initiated on form validation
     */
    $node = \Drupal::routeMatch()->getParameter('node');
    /**
     * set current path to be the one of the node in the url
     * in case there are multiple node entities related to it(the node)
     */
    $current_path = $node->toUrl()->setAbsolute()->toString();

    /**
     * get the SET environment, clientID and clientSecret from paypal settings
     */

    $apiContext = $this->paypal_settings->getApiContext();

    /**
     * Get the total Amount
     *
     */
    $sku = $this->generateSkuFromNodeTitle();
    $product = $sku; #make SKU
    $price = (float)$itemPrice;
    $shipping = 0.00;

    $total = $price + $shipping;

    $payer = new Payer();
    $payer->setPaymentMethod('paypal');

    $item = new Item();
    $item->setName($product)
      ->setCurrency($currency)#make dynamic from store configs
      ->setQuantity(1)
      ->setPrice($price);

    $itemList = new ItemList();
    $itemList->setItems([$item]);

    $details = new Details();
    $details->setShipping($shipping)
      #->setTax(2.00)
      ->setSubtotal($price);

    $amount = new Amount();
    $amount->setCurrency($currency)#TODO: Import this from currency
    ->setTotal($total)
      ->setDetails($details);

    $transaction = new Transaction();
    $transaction->setAmount($amount)
      ->setItemList($itemList)
      ->setDescription($sku)
      ->setInvoiceNumber(uniqid($sku));

    $redirectUrls = new RedirectUrls();
    $redirectUrls->setReturnUrl($current_path . '/?success=true')
      ->setCancelUrl($current_path. '/?success=false');

    $payment = new Payment();
    $payment->setIntent('sale')
      ->setPayer($payer)
      ->setRedirectUrls($redirectUrls)
      ->setTransactions([$transaction]);

    try{
      $payment->create($apiContext); #Create payment Else throw ERROR
    } catch (Exception $exception) {
      die($exception);
    }

    $approvalUlr = $payment->getApprovalLink() ;#The token redirection to PP
    $response = new TrustedRedirectResponse($approvalUlr);
    $form_state->setResponse($response);

  }

  /**
   * Checks if the node viewed has paypal field item
   * defined
   * called in getPrice().
   */
  public function getPaypalFieldItem(){

    /**
     * Load price from our PaypalItem fieldItem specific to each node
     * In our Paypal Entity form , in node field definitions
     *
     * @var \Drupal\node\Entity\Node $node
     */

    #$node = $this->getTheNode();
    $node = $this->getNodePerView();
    $bundle = $node->bundle();
    $field_definitions = $this->entity_field_manager->getFieldDefinitions('node', $bundle);

    foreach ($field_definitions as $key => $field) {
      if ($field->getType() == 'field_paypal') {
        $field_label = $field->getName();
        $paypalPrice = $node->get($field_label)->getValue();
      }
    }
    return $paypalPrice;
  }

  public function getTheNode(){
    /**
     * Load price from our PaypalItem fieldItem specific to each node
     * In our Paypal Entity form , in node field definitions
     * TODO:: check getNodePerView() for price value and sku
     *
     * @var \Drupal\node\Entity\Node $node
     */
    $node = \Drupal::routeMatch()->getParameter('node');

    return $node;
  }

  /**
   * Auto-generate sku from node title
   */
  public function generateSkuFromNodeTitle(){
    $title = $this->getNodePerView()->getTitle();
    //Trim to like 6 words (15 characters)
    $sku = substr($title, 0 , 15);

    return $sku;
  }

  /**
   * Get the price amount in relation to paypalField Item
   *
   * @param $paypalPrice
   *
   */
  public function getPrice($paypalPrice){
    foreach ($paypalPrice as $item => $value) {
      $price = $value['value'];

      return $price;
    }
  }

  /**
   * if multiple nodes are displayed on the same page
   * return the node specific to each paypal form
   * @return mixed|null
   */
  protected function getNodePerView(){
    $route_name = \Drupal::routeMatch()->getRouteName();

    if ($route_name == 'entity.node.canonical'){
      $node = \Drupal::routeMatch()->getParameter('node');
    }
    elseif ($route_name == 'entity.node.preview'){
      $node = \Drupal::routeMatch()->getParameter('node_preview');
    }

    return $node;
  }
}