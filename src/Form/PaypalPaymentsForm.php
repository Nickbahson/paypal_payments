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
use Drupal\node\Entity\Node;
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
    $form['price'] = array(
      '#type' => 'hidden',
      '#title' => t('Price'),
      '#size' => 25,
      '#description' => t("The price to pay."),
    );
    $form['entity_id'] = array(
      '#title' => t('The node id of the entity with the field'),
      '#type' => 'hidden',
      '#size' => 25,
      '#description' => t("The entity owning this."),
    );
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Pay With Paypal'),
    ];
    #dump($form);

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
    $price = $form_state->getValue('price');
    $entity_id = $form_state->getValue('entity_id');

    if (!$price){
      $form_state->setErrorByName('price', t('The price is not valid/set.'));
      return;
    }

    if (!$entity_id){
      $form_state->setErrorByName('entity_id', t('The Entity id is not set.'));
      return;
    }

    $form_state->setValue('price', $price);
    $form_state->setValue('entity_id', $entity_id);
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

    $entity_id = $form_state->getValue('entity_id');

    if ($entity_id) {
      $price = $form_state->getValue('price');
      $currency = $this->paypal_settings->getSetStoreCurrency();
      $node = $this->loadNodeFromForm($form_state);

      $this->paypalRequest($price, $currency, $form_state, $node);
    }


  }

  protected function paypalRequest($itemPrice, $currency, FormStateInterface $form_state, $node_in_field){
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
    $sku = $this->generateSkuFromNodeTitle($node_in_field);
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
   * Auto-generate sku from node title
   */
  public function generateSkuFromNodeTitle($node){
    /** @var \Drupal\node\Entity\Node $node */
    $title = $node->getTitle();
    //Trim to like 6 words (15 characters)
    $sku = substr($title, 0 , 15);

    return $sku;
  }


  /**
   * load node from form['entity_id']
   */
  protected function loadNodeFromForm(FormStateInterface $formState){
    /** @var \Drupal\node\Entity\Node $node */
    $nid = $formState->getValue('entity_id');
    $node = Node::load($nid);

    return $node;
  }
}
