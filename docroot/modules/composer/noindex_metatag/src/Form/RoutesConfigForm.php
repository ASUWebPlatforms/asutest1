<?php

namespace Drupal\noindex_metatag\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class Whitelist the Routes for no-index.
 */
class RoutesConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'noindex_metatag.routes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'noindex_metatag_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('noindex_metatag.routes');
    $form['enable_noindex'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable no-index metatag'),
      '#description' => $this->t('set noindex in the metatag for the below routes.'),
      '#default_value' => $config->get('enable_noindex'),
    ];
    $form['disable_routes'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Add noindex Routes'),
      '#default_value' => 'qa.*',
      '#description' => $this->t('A list of routes that you want to be add no-index ( enter each item per line). Wildcard "*" is supported. <br><b>Domain path:</b> A list of host routes (with out http:// or https://). If the domain name link https://dev.example.com means just add dev.*<br><u>Example:</u><br>local.*<br>dev.*<br><b>Relative path:</b> A list of Internal url with slash(/)<br><u>Example:</u><br>/admin/*<br>/user/reset/*<br/>/blogs<br/>/events'),
      '#default_value' => $config->get('disable_routes'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('noindex_metatag.routes')
      ->set('enable_noindex', $form_state->getValue('enable_noindex'))
      ->set('disable_routes', $form_state->getValue('disable_routes'))
      ->save();
  }

}
