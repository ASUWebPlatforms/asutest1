<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure GDMI participant reminders options.
 */
class GroupParticipantRemindersSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tb_gdmi_groups_participant_reminders_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tb_gdmi.groups_participant_reminders_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tb_gdmi.groups_participant_reminders_settings');

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config->get('title'),
    ];

    $form['body'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message Body'),
      '#description' => $this->t('The communications add admins message body.'),
      '#default_value' => $config->get('body.value'),
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
    $this->config('tb_gdmi.groups_participant_reminders_settings')
      ->set('title', $form_state->getValue('title'))
      ->set('body', $form_state->getValue('body'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
