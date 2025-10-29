<?php

namespace Drupal\tzfield\Plugin\Field\FieldType;

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\OptionsProviderInterface;

/**
 * Plugin implementation of the time zone field type.
 *
 * @FieldType(
 *   id = "tzfield",
 *   label = @Translation("Time zone"),
 *   description = @Translation("This field stores a time zone in the database."),
 *   default_widget = "tzfield_default",
 *   default_formatter = "basic_string"
 * )
 */
class TimeZoneItem extends FieldItemBase implements OptionsProviderInterface {

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.iterableValue
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'value' => [
          'type' => 'varchar',
          'length' => 50,
        ],
      ],
      'indexes' => [
        'value' => [
          'value',
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['value'] = DataDefinition::create('string')
      ->setLabel(t('Time zone'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('value')->getValue();
    return $value === NULL || $value === '';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.iterableValue
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    // @phpstan-ignore function.notFound
    $values['value'] = array_rand(class_exists(TimeZoneFormHelper::class) ? TimeZoneFormHelper::getOptionsList() : system_time_zones());
    return $values;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.iterableValue
   */
  public static function defaultFieldSettings() {
    return ['exclude' => [], 'default_site' => FALSE, 'default_user' => FALSE] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    parent::applyDefaultValue($notify);
    if ($this->getSetting('default_site')) {
      $this->setValue(['value' => \Drupal::config('system.date')->get('timezone.default')], $notify);
    }
    if ($this->getSetting('default_user') && \Drupal::currentUser()->getTimeZone()) {
      $this->setValue(['value' => \Drupal::currentUser()->getTimeZone()], $notify);
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.iterableValue,missingType.iterableValue
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = parent::fieldSettingsForm($form, $form_state);

    $element['exclude'] = [
      '#title' => $this->t('Time zones to be excluded from the option list'),
      '#type' => 'select',
      '#multiple' => TRUE,
      '#options' => $this->getPossibleOptions(),
      '#default_value' => $this->getSetting('exclude'),
      '#size' => 20,
      '#description' => $this->t('Any time zones selected here will be excluded from the allowed values.'),
    ];
    $element['default_site'] = [
      '#title' => $this->t("Use site's default time zone as default value"),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('default_site'),
      '#states' => ['disabled' => [':input[name="set_default_value"]' => ['checked' => TRUE]]],
    ];
    if (\Drupal::config('system.date')->get('timezone.user.configurable')) {
      $element['default_user'] = $element['default_site'];
      $element['default_user']['#title'] = $this->t("Use current user's time zone as default value");
      $element['default_user']['#default_value'] = $this->getSetting('default_user');
      $element['default_user']['#description'] = $this->t("If both <em>Use site's default time zone</em> and <em>Use current user's time zone</em> are checked and the current user does not have a time zone, the site's default time zone will be used as a fallback.");
    }
    return $element;
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   The array of possible time zone values.
   */
  public function getPossibleValues(?AccountInterface $account = NULL) {
    return \DateTimeZone::listIdentifiers();
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, array<string, string>|\Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The possible translated time zone options, keyed by time zone value and
   *   grouped by translated region.
   */
  public function getPossibleOptions(?AccountInterface $account = NULL) {
    // @phpstan-ignore function.notFound
    return class_exists(TimeZoneFormHelper::class) ? TimeZoneFormHelper::getOptionsListByRegion() : system_time_zones(FALSE, TRUE);
  }

  /**
   * {@inheritdoc}
   *
   * @return string[]
   *   The array of settable time zone values.
   */
  public function getSettableValues(?AccountInterface $account = NULL) {
    $timezones = $this->getPossibleValues();
    $exclude = $this->getSetting('exclude');
    if ($exclude && \is_array($exclude)) {
      $timezones = array_diff($timezones, $exclude);
    }
    return $timezones;
  }

  /**
   * {@inheritdoc}
   *
   * @return array<string, array<string, string>|\Drupal\Core\StringTranslation\TranslatableMarkup>
   *   The settable translated time zone options, keyed by time zone value and
   *   grouped by translated region.
   */
  public function getSettableOptions(?AccountInterface $account = NULL) {
    $timezones = $this->getPossibleOptions();
    $exclude = $this->getSetting('exclude');
    if ($exclude && \is_array($exclude)) {
      $exclude = array_filter($exclude, static function ($value) {
        return \is_string($value);
      });
      static::removeExclusionsFromGroupedOptions($timezones, $exclude);
    }
    return $timezones;
  }

  /**
   * Removes excluded time zones from the grouped options.
   *
   * @param array<string, array<string, string>|\Drupal\Core\StringTranslation\TranslatableMarkup> $timezones
   *   A list of translated time zone options, keyed by time zone value and
   *   grouped by translated region.
   * @param string[] $exclude
   *   The time zones to exclude from the options.
   *
   * @return void
   *   The provided time zone options are modified by reference.
   */
  public static function removeExclusionsFromGroupedOptions(array &$timezones, array $exclude) {
    // Exclusions will be removed by key rather than value.
    $exclude = array_flip($exclude);
    foreach ($timezones as $group_key => $timezone_group) {
      if (\is_array($timezone_group)) {
        $timezones[$group_key] = array_diff_key($timezone_group, $exclude);
        if (empty($timezones[$group_key])) {
          unset($timezones[$group_key]);
        }
      }
      // UTC is not in a group.
      elseif (isset($exclude[$group_key])) {
        unset($timezones[$group_key]);
      }
    }
  }

}
