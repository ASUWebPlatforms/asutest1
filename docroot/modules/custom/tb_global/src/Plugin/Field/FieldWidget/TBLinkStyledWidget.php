<?php

namespace Drupal\tb_global\Plugin\Field\FieldWidget;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webspark_utility\Plugin\Field\FieldWidget\LinkStyledWidget;

/**
 * Plugin implementation of the 'link' widget.
 *
 * @FieldWidget(
 *   id = "tb_styled_link",
 *   label = @Translation("CTA Button (TB)"),
 *   field_types = {
 *     "link"
 *   }
 * )
 */

class TBLinkStyledWidget extends LinkStyledWidget {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
        'placeholder_url' => '',
        'placeholder_title' => '',
        'available_styles' => array_keys(self::getStyleOptions()),
        'allow_style_selection' => TRUE,
        'default_style' => 'btn-blue btn',
        'size' => 'btn-default',
      ];
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $element = parent::formElement($items, $delta, $element, $form, $form_state);
    $item = $items[$delta];

    $options = $item->get('options')->getValue();

    $size = $this->getSetting('size');
    $style_options =  self::getStyleOptions($size);
    if ($this->getSetting('available_styles')) {
      $style_options = array_intersect_key($style_options, array_filter($this->getSetting('available_styles')));
    }

    if ($this->getSetting('allow_style_selection')) {
      $default_class = !empty($options['attributes']['class']) ? $options['attributes']['class'] : $this->getSetting('default_style');

      $element['options']['attributes']['class'] = [
        '#type' => 'select',
        '#required' => TRUE,
        '#title' => $this->t('Style'),
        '#options' => $style_options,
        '#default_value' => $default_class,
      ];
    }
    else {
      $element['options']['attributes']['class'] = [
        '#type' => 'hidden',
        '#value' => $this->getSetting('default_style'),
      ];
    }

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $elements = parent::settingsForm($form, $form_state);

    $size = $this->getSetting('size');
    $style_options = self::getStyleOptions($size);

    $elements['available_styles']['#options'] = $style_options;
    $elements['default_style']['#options'] = $style_options;

    return $elements;
  }

  private static function getStyleOptions($size = '') {
    return [
      $size . ' btn-blue btn' => t('Blue'),
      $size . ' btn-gold btn' => t('Gold'),
    ];
  }

}
