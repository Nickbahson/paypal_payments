<?php
/**
 * Alters nodes with the paypal field item for our custom functionalities
 * related to paypal api and drupal
 */

namespace Drupal\paypal_payments\Services;

use Drupal;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\core_event_dispatcher\Event\Entity\EntityViewEvent;
use Drupal\node\NodeInterface;
use Drupal\node\Routing\RouteSubscriber;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Exception\PayPalConnectionException;
use PayPalHttp\IOException;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

// TODO:: event not firing, moved to hook_node_view()
class payPalNodeViewSubscriber implements EventSubscriberInterface {

  /**
   * Since paypal_payment field is attached to nodes
   * should check if node has paypal payment field attached or for paypal
   * response params in the url
   *
   */
  public function paypalResponseEvent(GetResponseEvent $event) {
    $request = $event->getRequest();

    if ($request->attributes->get('_route') === 'entity.node.canonical'){
      $type = $request->get('type');
      $nid = $request->get('for_node');
      $order_id = $request->get('order_id');

      //dump($type);
      //dump($nid);
      //dump($order_id);
      //dump($request->attributes->get('_route'));

      // if all is set, else do nothing
      if (isset($type) && isset($nid) &&isset($order_id) && $type === 'pp_rq' /* pp_rg === paypal request abrv*/) {

        /** @var \Drupal\paypal_payments\Services\PayPalClient $charge_service */
        $charge_service = Drupal::service('paypal_payments.paypal_client');

        try {
          $charge_service->captureOrder($order_id);
        } catch (IOException $ex){

          \Drupal::messenger()->addError('Issue completing the transaction : '.$ex->getMessage());

        }

        //http://localhost/dru_8_tests/web/node/1?&for_node=20&order_id=ID_OTDRT6467&type=pp_rq


        $res = new RedirectResponse(strtok($request->getUri(), '?'));
        $res->send();
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
      KernelEvents::REQUEST => 'paypalResponseEvent'
    ];
  }

}
