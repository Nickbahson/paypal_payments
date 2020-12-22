<?php
/**
 * Alters nodes with the paypal field item for our custom functionalities
 * related to paypal api and drupal
 */

namespace Drupal\paypal_payments\Services;

use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountProxy;
use Drupal\core_event_dispatcher\Event\Entity\EntityViewEvent;
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

// TODO:: event not firing, moved to hook_node_view()
class payPalNodeViewSubscriber implements EventSubscriberInterface {

  /**
   * Since paypal_payment field is attached to nodes
   * should check if node has paypal payment field attached or for paypal
   * response params in the url
   *
   */
  public function alterEntityView(EntityViewEvent $event) {
    //$entity = $event->getEntity()

    // Only do this for entities of type Node.
    /*if ($entity instanceof NodeInterface) {
      $build = &$event->getBuild();
      $build['extra_markup'] = [
        '#markup' => 'this is extra markup',
      ];
    }
    */
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

}
