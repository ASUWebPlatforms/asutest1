<?php

namespace Drupal\tb_gdmi\Element;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element\Container;

/**
 * Provides a render element for webform flexbox.
 *
 * @FormElement("radios_container")
 */
class WebformRadiosContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function getInfo() {
    $info = parent::getInfo();
    $info['#theme_wrappers'] = ['gdmi_radios_container'];
    return $info;
  }

  /**
   * {@inheritdoc}
   */
  public static function processContainer(&$element, FormStateInterface $form_state, &$complete_form) {
    $element = parent::processContainer($element, $form_state, $complete_form);
    $element['#attributes']['class'][] = 'gdmi-' . str_replace('_','-', $element['#type']);
    $element['#attributes']['class'][] = 'gdmi-' . str_replace('_','-', $element['#type']) . '-' . str_replace('_','-', $element['#webform_key']);
    return $element;
  }

}
