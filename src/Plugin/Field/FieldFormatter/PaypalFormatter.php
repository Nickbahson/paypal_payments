<?php

namespace Drupal\paypal_payments\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\paypal_payments\Services\paypalSettings;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'paypal_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "paypal_formatter",
 *   label = @Translation("The output of Paypal filed on nodes"),
 *   field_types = {
 *     "field_paypal"
 *   }
 * )
 */
class PaypalFormatter extends FormatterBase implements ContainerFactoryPluginInterface{

  /**
   * @var \Drupal\paypal_payments\Services\paypalSettings
   */
  private $paypalSettings;

  /**
   * Creates an instance of the plugin.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The container to pull out services used in the plugin.
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   *
   * @return static
   *   Returns an instance of this plugin.
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $configuration['settings'],
      $configuration['label'],
      $configuration['view_mode'],
      $configuration['third_party_settings'],
      $container->get('paypal_payments.settings')
    );
  }

  public function __construct($plugin_id, $plugin_definition, \Drupal\Core\Field\FieldDefinitionInterface
  $field_definition, array $settings, $label, $view_mode, array $third_party_settings, paypalSettings $paypalSettings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);

    $this->paypalSettings = $paypalSettings;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        // Implement default settings.
      ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
        // Implement settings form.
      ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.

    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $form = \Drupal::formBuilder()->getForm('\Drupal\paypal_payments\Form\PaypalPaymentsForm');

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#theme' => 'field--field-paypal',
        '#paypal_payments_values' => [
          'value' => $this->paypalSettings->getSetStoreCurrency().' | '.$this->viewValue($item),
          'form' => $form,
        ]
      ];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    // The text value has no text format assigned to it, so the user input
    // should equal the output, including newlines.
    return nl2br(Html::escape($item->value));
  }

}
