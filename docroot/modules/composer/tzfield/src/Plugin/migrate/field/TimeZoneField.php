<?php

namespace Drupal\tzfield\Plugin\migrate\field;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Migration plugin for time zone field (tzfield).
 *
 * @MigrateField(
 *   id = "tzfield",
 *   core = {7},
 *   type_map = {
 *     "tzfield" = "tzfield"
 *   },
 *   source_module = "tzfield",
 *   destination_module = "tzfield"
 * )
 */
class TimeZoneField extends FieldPluginBase {

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   Field formatter map.
   */
  public function getFieldFormatterMap() {
    return [
      'tzfield_default' => 'basic_string',
      'tzfield_date' => 'tzfield_date',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   Field formatter map.
   */
  public function getFieldWidgetMap() {
    // By default, use the plugin ID for the widget types.
    return [
      'options_select' => 'tzfield_default',
      'tzfield_autocomplete' => 'tzfield_default',
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.iterableValue,missingType.return
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'value' => 'value',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
