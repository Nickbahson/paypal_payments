<?php

namespace Drupal\paypal_payments\Plugin\Block;

use Drupal\Core\Annotation\Translation;
use Drupal\Core\Block\Annotation\Block;
use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'PayPalPaymentsBlock' block.
 *
 * @Block(
 *  id = "pay_pal_payments_block",
 *  admin_label = @Translation("PayPal payments block"),
 * )
 */
class PayPalPaymentsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];
    $build['pay_pal_payments_block']['#markup'] = 'Implement PayPalPaymentsBlock.';

    return $build;
  }

}
