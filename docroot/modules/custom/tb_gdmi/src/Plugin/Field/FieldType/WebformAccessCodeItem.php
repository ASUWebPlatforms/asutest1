<?php

namespace Drupal\tb_gdmi\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'webform_access_code' field type.
 *
 * @FieldType(
 *   id = "webform_access_code",
 *   label = @Translation("Webform Access code"),
 *   module = "tb_gdmi",
 *   description = @Translation("Stores the access code values for a specific webform."),
 *   default_widget = "webform_access_code_widget",
 *   default_formatter = "webform_access_code_formatter"
 * )
 */
class WebformAccessCodeItem extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'webform_id' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'group_id' => [
          'type' => 'int',
          'size' => 'normal',
        ],
        'submission_id' => [
          'type' => 'int',
          'size' => 'normal',
        ],
        'access_code' => [
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ],
        'status' => [
          'type' => 'int',
          'size' => 'tiny',
          'default' => 0,
        ],
        'results' => [
          'type' => 'text',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {

    $properties['webform_id'] = DataDefinition::create('string')->setLabel(t('The webform to which the code belongs'))->setRequired(TRUE);
    $properties['group_id'] = DataDefinition::create('string')->setLabel(t('The group id'));
    $properties['submission_id'] = DataDefinition::create('string')->setLabel(t('The group id'));
    $properties['access_code'] = DataDefinition::create('string')->setLabel(t('The webform invitation code'))->setRequired(TRUE);
    $properties['results'] = DataDefinition::create('string')->setLabel(t('The results json encoded'));
    $properties['status'] = DataDefinition::create('boolean')->setLabel(t('Indicate whether the user has already completed the Webform'));

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $access_code = $this->get('access_code')->getValue();
    return empty($access_code);
  }

}