<?php

namespace Drupal\tb_layouts\Plugin\Layout;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * 
 * Horizontal Tabs layout with configurable tab's titles
 *
 */
class TbTabsHorizontalSection extends LayoutDefault implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $field_name = 'tab_titles';
    $config_exists = isset($this->configuration[$field_name]) ? TRUE : FALSE;

    $form[$field_name] = [];
    foreach($this->get_default_titles() as $tab) {
      $default_value = $tab;
      $sub_field = $tab;
      if($config_exists) {
        if(isset($this->configuration[$field_name][$sub_field])) {
          $default_value = $this->configuration[$field_name][$sub_field];
        }
      }

      $form[$field_name][$tab] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title for tab '.$tab),
        '#default_value' => $default_value,
        '#description' => $this->t(
          'Enter tab title, default is "@default_value"', ['@default_value' => $default_value]
        ),
      ];
    }

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['tab_titles'] = $form_state->getValue('tab_titles');
  }

  /**
   * Set default title for tabs
   */
  public function get_default_titles() {
    return ['first', 'second', 'third', 'fourth', 'fifth'];
  }

}
