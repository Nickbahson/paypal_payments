<?php
/**
 * subscribe to node requests:: and use this to alter our controller
 *
 * determine if a node has paypal field item
 */

namespace Drupal\paypal_payments\EventListenerHooks;


use Drupal\Core\Entity\EntityFieldManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class paypalPaymentsListener implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  private $entity_field_manager;

  public function __construct(EntityFieldManager $entity_field_manager) {
    $this->entity_field_manager = $entity_field_manager;
  }

  public function ifReturnFromPaypal(GetResponseEvent $event){
    /** @var \Drupal\node\Entity\Node $node */
    $node = \Drupal::routeMatch()->getParameter('node');
    $bundle = $node->bundle();
    $fields = $this->entity_field_manager->getFieldDefinitions('node', $bundle);
    dd($fields);
    $request = $event->getRequest();

    $useAlteredNodeViewController = $request->request->get('success');

    if ($useAlteredNodeViewController) {

    }
  }

  public static function getSubscribedEvents() {
    return [
      KernelEvents::REQUEST => 'ifReturnFromPaypal',
    ];
  }
}