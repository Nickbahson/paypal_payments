<?php
/**
 * Alters nodes with the paypal field item for our custom functionalities
 * related to paypal api and drupal
 */

namespace Drupal\paypal_payments\Services;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\hook_event_dispatcher\Event\Entity\EntityViewEvent;
use Drupal\node\NodeInterface;
use Drupal\node\Routing\RouteSubscriber;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Exception\PayPalConnectionException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;

class paypalNodeViewSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entity_field_manager;

  /**
   * @var \Drupal\paypal_payments\Services\paypalSettings
   */
  private $paypal_settings;

  /**
   * @var \Drupal\Core\Entity\EntityManager
   */
  private $entity_manager;

  /**
   * @var \Drupal\node\Routing\RouteSubscriber
   */
  private $node_route_subscriber;

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request_stack;

  /**
   * @var \Drupal\paypal_payments\Services\onPaypalPaymentsResponse
   */
  private $onPaypalPaymentsResponse;

  /**
   * @var \Drupal\Core\Session\AccountProxy
   */
  private $accountProxy;


  public function __construct(EntityFieldManager $entity_field_manager,
                              paypalSettings $paypal_settings,
                              EntityManager $entity_manager,
                              RouteSubscriber $node_route_subscriber,
                              RequestStack $request_stack,
                              onPaypalPaymentsResponse $onPaypalPaymentsResponse,
                              AccountProxy $accountProxy) {
    $this->entity_field_manager = $entity_field_manager;
    $this->paypal_settings = $paypal_settings;
    $this->entity_manager = $entity_manager;
    $this->node_route_subscriber = $node_route_subscriber;
    $this->request_stack = $request_stack;
    $this->onPaypalPaymentsResponse = $onPaypalPaymentsResponse;
    $this->accountProxy = $accountProxy;
  }

  public function alterEntityView(EntityViewEvent $event){
    $entity = $event->getEntity();
    // Only do this for entities of type Node.
    if ($entity instanceof NodeInterface) {
      /**
       * When node is on full view, we show our paypal form
       * widget so our event fires only then
       */
      if ($event->getViewMode() === 'full'){
        $request = $this->request_stack->getCurrentRequest();
        $bundle = $entity->bundle();
        $nid = $entity->id();
        $uid = $this->accountProxy->id();
        $fields = $this->entity_field_manager->getFieldDefinitions('node', $bundle);
        foreach ($fields as $key => $field){

          /**
           * Check if the node has a paypal field item
           */
          if ($field->getType() === 'field_paypal'){

            $current_path = $this->getRedirectRoute();

            $isPaypalReturn = $request->query->get('success');

            /**
             * if authorization was successful
             */
            if ($isPaypalReturn === 'true'){
              $request = $this->request_stack->getCurrentRequest();
              $paymentId = $request->query->get('paymentId');
              $token = $request->query->get('token');
              $payerId = $request->query->get('PayerID');
              $apiContext = $this->paypal_settings->getApiContext();

              $this->chargePaypal();
              $this->confirmPayment($paymentId, $apiContext, $nid, $uid);
              $response = new RedirectResponse($current_path);
              $response->send();
            }

            /**
             * If the user cancels and returns or there's some
             * other errors redirect to the page of origin with
             * some message
             */
            if ($isPaypalReturn === 'false'){
              $response = new RedirectResponse($current_path);
              $response->send();
              drupal_set_message(t('There was a problem authorizing the Charge'));
            }
          }
        }
      }
    }
  }

  /**
   * Returns an array of event names this subscriber wants to listen to.
   *
   * The array keys are event names and the value can be:
   *
   *  * The method name to call (priority defaults to 0)
   *  * An array composed of the method name to call and the priority
   *  * An array of arrays composed of the method names to call and respective
   *    priorities, or 0 if unset
   *
   * For instance:
   *
   *  * array('eventName' => 'methodName')
   *  * array('eventName' => array('methodName', $priority))
   *  * array('eventName' => array(array('methodName1', $priority), array('methodName2')))
   *
   * @return array The event names to listen to
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_VIEW => 'alterEntityView',
    ];
  }

  /**
   * Get the url of the parent node, if there are multiple
   * nodes on a single page, we will use the url of the parent node
   * ie the node all the other node entities on the page relate to
   */
  protected function getCurrentNode(){
    /** @var \Drupal\node\Entity\Node $node */
    $request = $this->request_stack->getCurrentRequest();
    $node = $request->attributes->get('node');

    return $node;
  }

  /**
   * Get redirect route in-relation to the parent node in-case
   * multiple nodes are displayed on a single page related to
   * the parent node entity
   */
  protected function getRedirectRoute(){
    $node = \Drupal::routeMatch()->getParameter('node');
    /**
     * set current path to be the one of the node in the url
     * in case there are multiple node entities related to it(the node)
     */
    $current_path = $node->toUrl()->setAbsolute()->toString();

    return $current_path;
  }

  /**
   * If auth was a success and we got paymentID, The TOKEN and the
   * PayerID go ahead and charge the user and post to database
   */
  protected function chargePaypal(){
    $request = $this->request_stack->getCurrentRequest();
    $paymentId = $request->query->get('paymentId');
    $token = $request->query->get('token');
    $payerId = $request->query->get('PayerID');

    #$apiContext = $this->getApiContext();
    $apiContext = $this->paypal_settings->getApiContext();

    $payment = Payment::get($paymentId, $apiContext);

    // Execute payment
    $execute = new PaymentExecution();
    $execute->setPayerId($payerId);

    try {
      $payment->execute($execute, $apiContext);

    } catch (PayPalConnectionException $exception) {
      //Payment failed
      #echo $exception->getCode();
      #echo $exception->getData();
      #die($exception);
      drupal_set_message(t('Unable to charge your account'));
    } catch (Exception $ex) {
      #dd($ex);
    }
  }

  /**
   * Confirm payment approval and post to database
   */
  protected function confirmPayment($paymentId, $apiContext, $nid, $uid){
    $payment = Payment::get($paymentId, $apiContext);

    // prepare values for inserting into our tables
    $sku = $payment->transactions[0]->description;
    $transaction_id = $payment->getId();
    $payment_amount = $payment->transactions[0]->amount->total;
    $payment_status = $payment->getState();
    $invoice_id = $payment->transactions[0]->invoice_number;
    $payer_email = $payment->payer->payer_info->email;
    $sale_id = $payment->transactions[0]->related_resources[0]->sale->id;

    /**
     * If the payment is successful it will have the state 'approved'.
     * Before we executed the payment it would have had the state 'created'.
     * If the request failed after we executed the payment the state would
     * be 'failed'
     */
    if ($payment_status === 'approved') {//post to DB

      /**
       * call our service method and post the data to the two tables
       * check Drupal\paypal_payments\Services\onPaypalPaymentsResponse
       */
      $this->onPaypalPaymentsResponse->insertIntoPaypalPayments($nid, $transaction_id, $sku, $uid, $payment_status);
      $this->onPaypalPaymentsResponse->insertIntoReceipts($nid, $transaction_id, $payer_email, $payment_amount, $sale_id, $invoice_id);
      drupal_set_message(t('Payment success'));#TODO:: more detailed maybe
    } else {
      drupal_set_message(t('Unable to charge your account'));
    }

  }
}