<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure GDMI settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tb_gdmi_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tb_gdmi.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['login_form_media'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Login form image.'),
      '#description' => $this->t('The login form media image.'),
      '#default_value' => $this->config('tb_gdmi.settings')->get('login_form_media'),
    ];

    $form['register_form_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Register form message.'),
      '#description' => $this->t('The register form bottom message.'),
      '#default_value' => $this->config('tb_gdmi.settings')->get('register_form_message.value'),
      '#format' => 'full_html',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tb_gdmi.settings')
      ->set('login_form_media', $form_state->getValue('login_form_media'))
      ->set('register_form_message', $form_state->getValue('register_form_message'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
