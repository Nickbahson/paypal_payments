<?php

namespace Drupal\paypal_payments\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityFieldManagerInterface;
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
class PaypalFormatter extends FormatterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      //TODO: Add settings maybe
    ] + parent::defaultSettings();
  }

  /**
   * Our database
   */
  protected $storage;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The paypal content render controller
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface
   */
  protected $viewBuilder;

  /**
   * The entity manager
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  #Drupal\Core\Entity\EntityFieldManagerInterface
  protected $entityManager;

  /**
   * The entity form builder
   *
   * @var \Drupal\Core\Entity\EntityFormBuilderInterface
   */
  protected $entityFormBuilder;

  /**
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * {@inheritdoc}
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
      $container->get('current_user'),
      $container->get('entity.manager'),
      $container->get('entity.form_builder'),
      $container->get('current_route_match')
    );
  }

  /**
   * Constructs a new PaypalFormatter.
   *
   * @param $plugin_id
   *  The plugin_id for the formatter.
   * @param $plugin_definition
   *  The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *  The definition of the field to which the formatter is associated.
   * @param array $settings
   *  The formatter settings.
   * @param $label
   *  The formatter label display setting.
   * @param $view_mode
   *  The view mode.
   * @param array $third_party_settings
   *  Third party settings.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *  The current user.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *  The entity manager
   * @param \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder
   *    The entity form builder.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *  The route match object.
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Throw Error if not defined
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   *
   */
  public function __construct($plugin_id, $plugin_definition, \Drupal\Core\Field\FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings, AccountInterface $current_user, EntityManagerInterface $entity_manager, EntityFormBuilderInterface $entity_form_builder, RouteMatchInterface $route_match) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->viewBuilder = $entity_manager->getViewBuilder('paypal');#todo create entity
    $this->viewBuilder = $entity_manager->getStorage('paypal');#TODO: create
    $this->currentUser = $current_user;
    $this->entityManager = $entity_manager;
    $this->entityFormBuilder = $entity_form_builder;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];
    $output = [];

    $field_name = $this->fieldDefinition->getName();
    $entity = $items->getEntity();#TODO :: create entity

    $status = $items->status;

    $paypal_settings = $this->getFieldSettings();

    $elements['#cache']['contexts'][] = 'user.permissions';

    if ($this->currentUser->hasPermission('access paypal_payments form')){
      $output['paypal_form'] = [
        '#lazy_builder' => [
          'paypal.lazy_builders:renderForm',
          [
            $entity->getEntityTypeId(),
            $entity->id(),
            $field_name,
          ],
        ],
        '#create_placeholder' => TRUE,
      ];
    }

    $elements[] = $output + [
      'paypal_form' => [],
      ];


    return $elements;
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
