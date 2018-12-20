<?php

namespace Drupal\paypal_payments\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\NumericItemBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_paypal' field type.
 *
 * @FieldType(
 *   id = "field_paypal",
 *   label = @Translation("Paypal payments"),
 *   description = @Translation("Stores data for paypal field"),
 *   default_widget = "paypal_widget",
 *   default_formatter = "paypal_formatter"
 * )
 */
class PaypalItem extends NumericItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
        'unsigned' => FALSE,
        // Valid size property values include: 'tiny', 'small', 'medium', 'normal'
        // and 'big'.
        'size' => 'normal',
      ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['value'] = DataDefinition::create('integer')
      ->setLabel(new TranslatableMarkup('Integer value'))
      #->setSetting('case_sensitive', $field_definition->getSetting('case_sensitive'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'value' => [
          'type' => 'int',
          // Expose the 'unsigned' setting in the field item schema.
          'unsigned' => $field_definition->getSetting('unsigned'),
          // Expose the 'size' setting in the field item schema. For instance,
          // supply 'big' as a value to produce a 'bigint' type.
          'size' => $field_definition->getSetting('size'),
        ],
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
    if ($this->getSetting('unsigned')){
      $constraints_manager = \Drupal::typedDataManager()->getValidationConstraintManager();
      $constraints[] = $constraints_manager->create('ComplexData', [
        'value' => [
          'Range' => [
            'min' => 0,
            'minMessage' => new TranslatableMarkup ('%name: The integer must be larger or equal tp %min.',[
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
    #$random = new Random();
    #$values['value'] = $random->word(mt_rand(1, $field_definition->getSetting('max_length')));
    $min = $field_definition->getSetting('min') ?: 0;
    $max = $field_definition->getSetting('max') ?: 999;
    $values['value'] = mt_rand($min, $max);
    return $values;
  }

  /**

  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
  $elements = [];

  $elements['max_length'] = [
  '#type' => 'number',
  '#title' => t('Maximum length'),
  '#default_value' => $this->getSetting('max_length'),
  '#required' => TRUE,
  '#description' => t('The maximum length of the field in characters.'),
  '#min' => 1,
  '#disabled' => $has_data,
  ];

  return $elements;
  }

  public function isEmpty() {
  $value = $this->get('value')->getValue();
  return $value === NULL || $value === '';
  }
   */

}