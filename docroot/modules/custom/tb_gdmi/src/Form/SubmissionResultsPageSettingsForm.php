<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure GDMI Submission Results Page for this site.
 */
class SubmissionResultsPageSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tb_gdmi_submission_results_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tb_gdmi.submission_results_page_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tb_gdmi.submission_results_page_settings');
    $form['image'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Page top image.'),
      '#description' => $this->t('The image showed at the top of the page.'),
      '#default_value' => $config->get('image'),
    ];

    $form['intro_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Intro text.'),
      '#description' => $this->t('The text showed at the top of the page.'),
      '#default_value' => $config->get('intro_text.value'),
      '#format' => 'full_html',
    ];

    $form['bottom_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Bottom text.'),
      '#description' => $this->t('The text showed at the bottom of the page.'),
      '#default_value' => $config->get('bottom_text.value'),
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
    $this->config('tb_gdmi.submission_results_page_settings')
      ->set('image', $form_state->getValue('image'))
      ->set('intro_text', $form_state->getValue('intro_text'))
      ->set('bottom_text', $form_state->getValue('bottom_text'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
