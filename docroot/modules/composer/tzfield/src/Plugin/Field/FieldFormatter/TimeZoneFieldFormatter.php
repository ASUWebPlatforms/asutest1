<?php

namespace Drupal\tzfield\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Plugin implementation of the 'tzfield_date' formatter.
 *
 * @FieldFormatter(
 *   id = "tzfield_date",
 *   label = @Translation("Formatted current date"),
 *   field_types = {
 *     "tzfield"
 *   }
 * )
 *
 * @phpstan-ignore missingType.generics
 */
class TimeZoneFieldFormatter extends FormatterBase implements TrustedCallbackInterface {

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary[] = $this->t('Date format string: %format', ['%format' => $this->getSetting('format')]);
    return $summary;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.generics,missingType.iterableValue
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $element = [];
    foreach ($items as $delta => $item) {
      if (!isset($item->value)) {
        continue;
      }
      // Render each element as markup.
      $placeholder = Crypt::randomBytesBase64();
      $element[$delta]['#markup'] = $placeholder;
      $element[$delta]['#attached']['placeholders'][$placeholder]['#lazy_builder'] = [
        [$this, 'getRenderableFormattedDate'], [$item->value],
      ];
    }
    return $element;
  }

  /**
   * Returns the formatted date as a renderable array.
   *
   * @return string[]
   *   The renderable array.
   */
  public function getRenderableFormattedDate(string $timezone): array {
    $dateTime = new DrupalDateTime();
    $dateTime->setTimezone(new \DateTimeZone($timezone));
    $format = $this->getSetting('format');
    if (!\is_string($format)) {
      $format = 'T';
    }
    return ['#markup' => $dateTime->format($format)];
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.iterableValue
   */
  public static function defaultSettings() {
    return [
      'format' => 'T',
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-ignore missingType.iterableValue,missingType.iterableValue
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    $form['format'] = [
      '#title' => $this->t('Date format string'),
      '#description' => $this->t('Enter a <a rel="noreferrer" href="https://www.php.net/manual/en/datetime.format.php#refsect1-datetime.format-parameters">PHP date format string</a>, e.g. <em>T</em> to display the current time zone abbreviation.'),
      '#type' => 'textfield',
      '#default_value' => $this->getSetting('format'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function trustedCallbacks() {
    return ['getRenderableFormattedDate'];
  }

}
