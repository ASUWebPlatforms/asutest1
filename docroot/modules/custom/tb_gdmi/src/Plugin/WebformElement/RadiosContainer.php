<?php

namespace Drupal\tb_gdmi\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformElement\ContainerBase;

/**
 * Provides a 'radios_container' element.
 *
 * @WebformElement(
 *   id = "radios_container",
 *   api = "https://api.drupal.org/api/drupal/core!lib!Drupal!Core!Render!Element!Fieldset.php/class/Fieldset",
 *   label = @Translation("Radios container"),
 *   description = @Translation("Provides an element for a group of form radios elements."),
 *   category = @Translation("Containers"),
 * )
 */
class RadiosContainer extends ContainerBase {

  const NUM_KEYS = 5;

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties() {

    $keys = [];
    for ($i = 0; $i < self::NUM_KEYS; $i++) {
      $keys['key_' . ($i + 1)] = '';
    }

    $properties = [
      'title' => '',
      // Attributes.
      'attributes' => [],
      // Randomize.
      'randomize' => FALSE,
      // Flexbox.
      'flex' => 1,
      // Conditional logic.
      'states' => [],
      'states_clear' => TRUE,
      // Format.
      'format' => $this->getItemDefaultFormat(),
      'format_html' => '',
      'format_text' => '',
      'format_attributes' => [],
      'total_keys' => 0,
    ];

    $properties = array_merge($properties, $keys);


    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\webform\WebformInterface $webform */
    $webform = $form_state->getFormObject()->getWebform();

    $form['total_keys'] = [
      '#type' => 'hidden',
      '#value' => self::NUM_KEYS 
    ];

    $form['key_list'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Keys descriptions')
    ];

    for ($i = 0; $i < self::NUM_KEYS; $i++) {
      $form['key_list']['key_' . ($i + 1)]= [
        '#type' => 'textfield',
        '#title' => 'Key ' . ($i + 1),
        '#default_value' => $webform->getSetting('Key ' . ($i + 1), TRUE) ?? ''
      ];
    }
    
    return $form;
  }

}
