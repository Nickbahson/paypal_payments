<?php

namespace Drupal\paypal_payments\Plugin\Field\FieldType;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

/**
 * Plugin implementation of the 'field_paypal' field type.
 *
 * @FieldType(
 *   id = "field_paypal",
 *   label = @Translation("Paypal"),
 *   description = @Translation("Stores data for paypal field"),
 *   default_widget = "paypal_widget",
 *   default_formatter = "paypal_formatter"
 * )
 */
class PaypalItem extends EntityReferenceItem {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'unsigned' => FALSE,
        // Valid size property values include: 'tiny', 'small', 'medium', 'normal'
        // and 'big'.
        'size' => 'normal',
        'target_type' => \Drupal::moduleHandler()->moduleExists('node') ? 'node' : 'user',
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
        'min' => '',
        'max' => '',
        'prefix' => '',
        'suffix' => '',
      ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $target_type_info = \Drupal::entityManager()->getDefinition($settings['target_type']);

    $target_id_data_type = 'string';
    if ($target_type_info->entityClassImplements(FieldableEntityInterface::class)) {
      $id_definition = \Drupal::entityManager()->getBaseFieldDefinitions($settings['target_type'])[$target_type_info->getKey('id')];
      if ($id_definition->getType() === 'integer') {
        $target_id_data_type = 'integer';
      }
    }

    if ($target_id_data_type === 'integer') {
      $target_id_definition = DataReferenceTargetDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $target_type_info->getLabel()]))
        ->setSetting('unsigned', TRUE);
    }
    else {
      $target_id_definition = DataReferenceTargetDefinition::create('integer')
        ->setLabel(new TranslatableMarkup('@label ID', ['@label' => $target_type_info->getLabel()]));
    }
    $target_id_definition->setRequired(TRUE);
    $properties['target_id'] = $target_id_definition;

    $properties['entity'] = DataReferenceDefinition::create('entity')
      ->setLabel($target_type_info->getLabel())
      ->setDescription(new TranslatableMarkup('The referenced entity'))
      // The entity object is computed out of the entity ID.
      ->setComputed(TRUE)
      ->setReadOnly(FALSE)
      ->setTargetDefinition(EntityDataDefinition::create($settings['target_type']))
      // We can add a constraint for the target entity type. The list of
      // referenceable bundles is a field setting, so the corresponding
      // constraint is added dynamically in ::getConstraints().
      ->addConstraint('EntityType', $settings['target_type']);

    #$properties = parent::propertyDefinitions($field_definition);
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Integer value'))
      #->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;#TODO
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
   /** return [
      'columns' => [
        'value' => [
          'type' => 'int',
          // Expose the 'unsigned' setting in the field item schema.
          'unsigned' => $field_definition->getSetting('unsigned'),
          // Expose the 'size' setting in the field item schema. For instance,
          // supply 'big' as a value to produce a 'bigint' type.
          'size' => $field_definition->getSetting('size'),
        ],
        'target_id' => [
          'description' => 'The ID of the Node',
          'type' => 'int',
          'unsigned' => TRUE,
        ],
      ],
      'indexes' => [
        'target_id' => ['target_id'],
      ],
    ];
    */
   $target_type = $field_definition->getSetting('target_type');
   $target_type_info = \Drupal::entityManager()->getDefinition($target_type);
   $properties = static::propertyDefinitions($field_definition)['target_id'];
   if ($target_type_info->entityClassImplements(FieldableEntityInterface::class) && $properties->getDataType() === 'integer') {
     $columns = [
       'target_id' => [
         'description' => 'The ID of the target entity.',
         'type' => 'int',
         'unsigned' => TRUE,
       ],
       'value' => [
         'type' => 'int',
         // Expose the 'unsigned' setting in the field item schema.
         'unsigned' => $field_definition->getSetting('unsigned'),
         // Expose the 'size' setting in the field item schema. For instance,
         // supply 'big' as a value to produce a 'bigint' type.
         'size' => $field_definition->getSetting('size'),
       ],
     ];
   }
   else {
     $columns = [
       'target_id' => [
         'description' => 'The ID of the target entity.',
         'type' => 'varchar_ascii',
         // If the target entities act as bundles for another entity type,
         // their IDs should not exceed the maximum length for bundles.
         'length' => $target_type_info->getBundleOf() ? EntityTypeInterface::BUNDLE_MAX_LENGTH : 255,
       ],
       'value' => [
         'type' => 'int',
         // Expose the 'unsigned' setting in the field item schema.
         'unsigned' => $field_definition->getSetting('unsigned'),
         // Expose the 'size' setting in the field item schema. For instance,
         // supply 'big' as a value to produce a 'bigint' type.
         'size' => $field_definition->getSetting('size'),
       ],
     ];
   }
   $schema = [
     'columns' => $columns,
     'indexes' => [
       'target_id' => ['target_id'],
     ],
   ];

   return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function getConstraints() {
    $constraints = parent::getConstraints();

    // If this is an unsigned integer, add a validation constraint for the
    // integer to be positive.
    if ($this->getSetting('unsigned')) {
      $constraint_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraint_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'min' => 0,
            'minMessage' => t('%name: The integer must be larger or equal to %min.', [
              '%name' => $this->getFieldDefinition()->getLabel(),
              '%min' => 0,
            ]),
          ],
        ],
      ]);
    }

    return $constraints;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    $min = $field_definition->getSetting('min') ?: 0;
    $max = $field_definition->getSetting('max') ?: 999;
    $values['value'] = mt_rand($min, $max);
    return $values;
  }

  /**
   * {@inheritdoc
   */
  public function getValue() {
    $values = parent::getValue(); // TODO: Change the autogenerated stub

    // If there is an unsaved entity, return it as part of the field item values
    // to ensure idempotency of getValue() / setValue().
    if ($this->hasNewEntity()) {
      $values['entity'] = $this->getEntity();
    }
    return $values;
  }


  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    #$value = $this->get('value')->getValue();
    #return $value === NULL || $value === '';
    // Avoid loading the entity by first checking the 'target_id'.
    //if ($this->target_id !== NULL) {
      //return FALSE;
    //}

    //if ($this->entity)
    if ($this->target_id !== NULL) {
      return FALSE;
    }
    if ($this->entity && $this->enity instanceof EntityInterface) {
      return FALSE;
    }
    return TRUE;
  }

}
