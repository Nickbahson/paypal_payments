<?php
/**
 * Created by PhpStorm.
 * User: Nick
 * Date: 11/9/2018
 * Time: 4:11 PM
 */

namespace Drupal\paypal_payments\Services;


use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;

class PaypalPaymentsLazyBuilders {

  /**
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * @var \Drupal\Core\Render\RendererInterface;
   */
  protected $renderer;

  /**
   * Constructs a new PaypalPaymentsLazyBuilders  object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *  The entity manager service.
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *  The entity form builder service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *  The current logged in user.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *  The module handler service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *  The renderer service.
   */
  public function __construct(EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, AccountInterface $current_user, ModuleHandlerInterface $module_handler, RendererInterface $renderer){

    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->currentUser = $current_user;
    $this->moduleHandler = $module_handler;
    $this->renderer = $renderer;
  }


  /**
   * #lazy_builder callback; builds the paypal form.
   *
   * @param string $paypal_entity_type_id
   *   The paypal entity type ID.
   * @param string $paypal_entity_id
   *   The paypal (node_entity) entity ID.
   * @param string $field_name
   *   The paypal field name.
   *
   * @return array
   *   A renderable array containing the paypal form.
   */
  public function renderForm($paypal_entity_type_id, $paypal_entity_id, $field_name){
    $values = [
      'entity_type' => $paypal_entity_type_id,
      'entity_id' => $paypal_entity_id,
      'field_name' => $field_name,
      'pid' => NULL,
    ];
    $paypal = $this->entityManager->getStorage('paypal')->create($values);
    return $this->entityFormBuilder->getForm($paypal);
  }

}