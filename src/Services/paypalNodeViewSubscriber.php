<?php
/**
 * Alters nodes with the paypal field item for our custom functionalities
 * related to paypal api and drupal
 */

namespace Drupal\paypal_payments\Services;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\hook_event_dispatcher\Event\Entity\EntityViewEvent;
use Drupal\node\NodeInterface;
use Drupal\node\Routing\RouteSubscriber;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

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


  public function __construct(EntityFieldManager $entity_field_manager,
                              paypalSettings $paypal_settings,
                              EntityManager $entity_manager,
                              RouteSubscriber $node_route_subscriber,
                              RequestStack $request_stack) {
    $this->entity_field_manager = $entity_field_manager;
    $this->paypal_settings = $paypal_settings;
    $this->entity_manager = $entity_manager;
    $this->node_route_subscriber = $node_route_subscriber;
    $this->request_stack = $request_stack;
  }

  public function alterEntityView(EntityViewEvent $event){
    $entity = $event->getEntity();
    // Only do this for entities of type Node.
    if ($entity instanceof NodeInterface) {
      $build = &$event->getBuild();
      /**
       * When node is on full view this is when to show paypal form
       * widget so our event fires only then
       */
      if ($event->getViewMode() === 'full'){
        $request = $this->request_stack->getCurrentRequest();
        $bundle = $entity->bundle();
        $fields = $this->entity_field_manager->getFieldDefinitions('node', $bundle);
        foreach ($fields as $key => $field){

          /**
           * Check if the node has a paypal field item
           */
          if ($field->getType() === 'field_paypal'){
            //TODO:: create a variable with the token..maybe store in keyvalue and compare with return token for added security
            $current_path = $this->getRedirectRoute();

            $isPaypalReturn = $request->query->get('success');
            /**
             * if authorization was successful
             */
            if ($isPaypalReturn === 'true'){

              $this->chargePaypal(); #charge account

              $response = new RedirectResponse($current_path);
              $response->send();
              #$response_event->setResponse($response);
            }

            /**
             * If the user cancels and returns or there's some
             * other errors redirect to the page of origin with
             * some message
             */
            if ($isPaypalReturn === 'false'){
              $response = new RedirectResponse($current_path);
              $response->send();
              #$response_event->setResponse($response);
              drupal_set_message(t('There was a problem authorizing the Charge'));
            }

          }
        }
      }

      //dd($this->entity_field_manager)
      $build['extra_markup'] = [
        '#markup' => 'this is extra markup',

      ];
    }
  }

  public function paypalPaymentsReturn(GetResponseEvent $event){

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
    $request = $this->request_stack->getCurrentRequest();
    $current_path = $request->getSchemeAndHttpHost().$request->getBaseUrl().$request->getPathInfo();

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

    //Create a charge object
    $paypal = new ApiContext(
      new OAuthTokenCredential(
        $this->paypal_settings->getClientId(),
        $this->paypal_settings->getClientSecret()
      )
    );

    #get config environment and set
    $paypal->setConfig(
      ['mode' => $this->paypal_settings->getSetEnvironment()]
    );

    $payment = Payment::get($paymentId, $paypal);

    // Execute payment
    $execute = new PaymentExecution();
    $execute->setPayerId($payerId);

    try {
      $payment->execute($execute, $paypal);

      //TODO:: Load from paypal form and save to paypal_payments
      drupal_set_message('Payment success');
    } catch (Exception $exception) {
      $data = json_decode($exception->getData());
      echo $data->message;
    }
  }
}