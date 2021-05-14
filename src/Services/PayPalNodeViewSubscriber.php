<?php
/**
 * Alters nodes with the paypal field item for our custom functionalities
 * related to paypal api and drupal
 */

namespace Drupal\paypal_payments\Services;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;
// TODO:: event not firing, moved to hook_node_view()
class PayPalNodeViewSubscriber implements EventSubscriberInterface {

  /** @var RequestStack */
  // TODO:: remove not used
  protected $requestStack;
  public function setRequestStack(RequestStack $requestStack){
    $this->requestStack = $requestStack;
  }

  /**
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *
   * Since paypal_payment field is attached to nodes
   * should check if route is node canonical and has the relevant
   * response params in the url to initiate the charge
   */
  public function paypalResponseEvent(ResponseEvent $event) {
    $request = $event->getRequest();
    if ($request->attributes->get('_route') === 'entity.node.canonical'
    && $request->get('type') && $request->get('for_node') && $request->get('order_id') && $request->get('type') === 'pp_rq' /* pp_rg === paypal request abrv*/){

      $nid = $request->get('for_node');
      $order_id = $request->get('order_id');


      // if all is set, else do nothing
      /** @var \Drupal\paypal_payments\Services\PayPalClient $charge_service */
      $charge_service = \Drupal::service('paypal_payments.paypal_client');

      $charge_service->captureOrder($order_id, $nid);
      //http://localhost/dru_8_tests/web/node/1?&for_node=20&order_id=ID_OTDRT6467&type=pp_rq

      // remove only paypal related tokens and redirect to that for NB:: uniformity
      $remove = "?for_node=".$nid."&order_id=".$order_id."&type=pp_rq";


      $return = str_replace($remove, '', $request->getUri());

      //$res = new RedirectResponse(strtok($request->getUri(), '?'));

      //return new RedirectResponse($return);
      //$res->send();
      //return;
      $event->setResponse(new RedirectResponse($return));
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
      KernelEvents::RESPONSE => 'paypalResponseEvent'
    ];
  }

}
