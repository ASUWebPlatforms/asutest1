<?php

namespace Drupal\tb_gdmi\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;

/**
 * Plugin implementation of the 'webform_access_code_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "webform_access_code_formatter",
 *   label = @Translation("Webform Access code Formatter"),
 *   field_types = {
 *     "webform_access_code"
 *   }
 * )
 */
class WebformAccessCodeFormatter extends FormatterBase
{
  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode)
  {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = [
        '#markup' => $this->t('Webform ID: @webform_id, Group Id: @group_id, Access Code: @access_code, Completed: @status, Results: @results, Submission Id: @submission_id', [
          '@webform_id' => $item->webform_id,
          '@group_id' => $item->group_id,
          '@access_code' => $item->access_code,
          '@status' => $item->status ? $this->t('Yes') : $this->t('No'),
          '@results' => $item->results,
          '@submission_id' => $item->submission_id,
        ]),
      ];
    }

    return $elements;
  }
}
