<?php

namespace Drupal\tb_gdmi_hubspot\Form;

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
    return 'tb_gdmi_hubspot_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tb_gdmi_hubspot.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Hubspot API Access Token.'),
      '#default_value' => $this->config('tb_gdmi_hubspot.settings')->get('access_token'),
    ];

    $form['send_test_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send test email.')
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
    $this->config('tb_gdmi_hubspot.settings')
      ->set('access_token', $form_state->getValue('access_token'))
      ->save();

    if ($form_state->getValue('send_test_email') == 1) {
      \Drupal::service('tb_gdmi_hubspot.transactional_emails')->testEmail();
    }

    parent::submitForm($form, $form_state);
  }

}
