<?php

namespace Drupal\tzfield\Plugin\Field\FieldWidget;

use Drupal\Core\Datetime\TimeZoneFormHelper;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tzfield\Plugin\Field\FieldType\TimeZoneItem;

/**
 * Plugin implementation of the time zone default widget.
 *
 * @FieldWidget(
 *   id = "tzfield_default",
 *   label = @Translation("Time zone"),
 *   field_types = {
 *     "tzfield"
 *   }
 * )
 */
class TimeZoneDefaultWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.generics,missingType.iterableValue,missingType.iterableValue,missingType.iterableValue,missingType.iterableValue
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    // @phpstan-ignore function.notFound
    $timezones = class_exists(TimeZoneFormHelper::class) ? TimeZoneFormHelper::getOptionsListByRegion(!$element['#required']) : system_time_zones(!$element['#required'], TRUE);
    $exclude = $this->getFieldSetting('exclude');
    if ($exclude && \is_array($exclude)) {
      $exclude = array_filter($exclude, static function ($value) {
        return \is_string($value);
      });
      TimeZoneItem::removeExclusionsFromGroupedOptions($timezones, $exclude);
    }
    $element['value'] = $element + [
      '#type' => 'select',
      '#options' => $timezones,
      '#default_value' => $items[$delta]->value ?? NULL,
    ];
    return $element;
  }

}
