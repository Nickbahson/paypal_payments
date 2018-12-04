<?php

namespace Drupal\paypal_payments\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the Paypal entity.
 *
 * @ingroup paypal_payments
 *
 * @ContentEntityType(
 *   id = "paypal",
 *   label = @Translation("Paypal"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\paypal_payments\PaypalListBuilder",
 *     "views_data" = "Drupal\paypal_payments\Entity\PaypalViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\paypal_payments\Form\PaypalForm",
 *       "add" = "Drupal\paypal_payments\Form\PaypalForm",
 *       "edit" = "Drupal\paypal_payments\Form\PaypalForm",
 *       "delete" = "Drupal\paypal_payments\Form\PaypalDeleteForm",
 *     },
 *     "access" = "Drupal\paypal_payments\PaypalAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\paypal_payments\PaypalHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "paypal",
 *   admin_permission = "administer paypal entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "sku",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *      "payment_status" = "payment_status",
 *   },
 *   links = {
 *     "canonical" = "/content/paypal/paypal/{paypal}",
 *     "add-form" = "/content/paypal/paypal/add",
 *     "edit-form" = "/content/paypal/paypal/{paypal}/edit",
 *     "delete-form" = "/content/paypal/paypal/{paypal}/delete",
 *     "collection" = "/content/paypal/paypal",
 *   },
 *   field_ui_base_route = "paypal.settings"
 * )
 */
class Paypal extends ContentEntityBase implements PaypalInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getSku() {
    return $this->get('sku')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setSku($sku) {
    $this->set('sku', $sku);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymentStatus(){
    return $this->get('payment_status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setPaymentStatus($payment_status){
    $this->set('payment_status', $payment_status);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the Paypal entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['sku'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sku'))
      ->setDescription(t('The Sku of the Paypal entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the Paypal is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -3,
      ]);

    $fields['entity_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Entity ID'))
      ->setDescription(t('The ID of the entity of which this Payment is a related to.'))
      ->setRequired(TRUE)

      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])

    ;

    $fields['price'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Price'))
      ->setDescription(t('The Price in Store currency of the Product'))
      ->setRequired(TRUE)
      ->setDefaultValue(0)

      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
    ;

    $fields['payment_status'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Payment Status'))
      ->setDescription(t('The Payment status'))
      ->setRequired(FALSE)
      ->setDefaultValue('Failed')
      /**
       * TODO:: get a better way to set the default value for field
       * payment_status
       */

      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
    ;

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId() {
    return $this->get('entity_id')->target_id;
  }

  public function setEntityId($entity_id) {
    return $this->set('entity_id', $entity_id);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrice() {
    return $this->get('price')->value;
  }

  public function setPrice($price){
    return $this->set('price', $price);
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status');
  }

  public function setStatus($status) {
    return $this->set('status', $status);
  }

  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    #TODO:: call other functions, like send mail to user and so on
    #$this->setEntityId();
    #$this->setPrice();
  }

}
