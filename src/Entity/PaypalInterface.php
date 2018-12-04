<?php

namespace Drupal\paypal_payments\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Paypal entities.
 *
 * @ingroup paypal_payments
 */
interface PaypalInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  // Add get/set methods for your configuration properties here.

  /**
   * Gets the Paypal name.
   *
   * @return string
   *   Name of the Paypal.
   */
  public function getSku();

  /**
   * Sets the Paypal name.
   *
   * @param string $name
   *   The Paypal name.
   *
   * @return \Drupal\paypal_payments\Entity\PaypalInterface
   *   The called Paypal entity.
   */
  public function setSku($name);

  /**
   * Gets the Paypal creation timestamp.
   *
   * @return int
   *   Creation timestamp of the Paypal.
   */
  public function getCreatedTime();

  /**
   * Sets the Paypal creation timestamp.
   *
   * @param int $timestamp
   *   The Paypal creation timestamp.
   *
   * @return \Drupal\paypal_payments\Entity\PaypalInterface
   *   The called Paypal entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the Paypal published status indicator.
   *
   * Unpublished Paypal are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the Paypal is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a Paypal.
   *
   * @param bool $published
   *   TRUE to set this Paypal to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\paypal_payments\Entity\PaypalInterface
   *   The called Paypal entity.
   */
  public function setPublished($published);

  /**
   * Returns the EntityId of the node product entity related to the payment made
   * @return
   */
  public function getEntityId();

  /**
   * Returns the paid price the user is charged
   *
   *  @return integer
   */
  public function getPrice();

  /**
   * Returns the payment status (Success, pending bla bla)
   *
   *  @return string
   */
  public function getStatus();


}
