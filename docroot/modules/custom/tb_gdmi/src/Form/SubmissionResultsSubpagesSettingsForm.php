<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure GDMI Submission Results Subpages for this site.
 */
class SubmissionResultsSubpagesSettingsForm extends ConfigFormBase {

  /**
   * Results pages names list.
   */
  const RESULT_PAGES = [ 
    'psychological' => 'Psychological Capital',
    'intellectual' => 'Intellectual Capital',
    'social' => 'Social Capital',
    'digital' => 'Digital Capital',
  ];

  /**
   * Results capital divisions.
   */
  const CAPITAL_DIVISIONS = [ 
    'psychological' => ['Passion for Diversity', 'Quest for Adventure', 'Self-Assurance'],
    'intellectual' => ['Global Business Savvy', 'Cosmopolitan Outlook', 'Cognitive Complexity'],
    'social' => ['Intercultural Empathy', 'Interpersonal Impact', 'Diplomacy'],
    'digital' => ['Digital Advocacy', 'Digital Implementation', 'Growth Mindset'],
  ];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tb_gdmi_submission_results_subpages_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tb_gdmi.submission_results_subpages_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('tb_gdmi.submission_results_subpages_settings');

    $form['horizontal_tabs'] = [
      '#type' => 'horizontal_tabs',
    ];

    foreach (self::RESULT_PAGES as $key => $title) {

      $form[$key] = [
        '#type' => 'details',
        '#title' => $title,
        '#group' => 'horizontal_tabs',
      ];
  
      $form[$key]['image'] = [
        '#type' => 'media_library',
        '#allowed_bundles' => ['image'],
        '#title' => $this->t('Page top image.'),
        '#description' => $this->t('The image showed at the top of the page.'),
        '#default_value' => $config->get($key . '.image'),
        '#parents' => [$key, 'image'],
      ];
  
      $form[$key]['intro_text'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Intro text.'),
        '#description' => $this->t('The text showed at the top of the page.'),
        '#default_value' => $config->get($key . '.intro_text.value'),
        '#format' => 'full_html',
        '#parents' => [$key, 'intro_text'],
      ];

      $form[$key]['items'] = [
        '#type' => 'container',
      ];

      $items = $config->get($key . '.items');
      for ($i=0; $i < 3 ; $i++) { 

        $form[$key]['items'][$i] = [
          '#type' => 'details',
          '#title' => SELF::CAPITAL_DIVISIONS[$key][$i],
          '#open' => FALSE,
        ];
        
        $image = isset($items[$i]['image']) ? $items[$i]['image'] : NULL;
        $form[$key]['items'][$i]['image'] = [
          '#type' => 'media_library',
          '#allowed_bundles' => ['image'],
          '#title' => $this->t('Icon Image.'),
          '#default_value' => $image,
          '#parents' => [$key, 'items', $i, 'image']
        ];
      
        $title = isset($items[$i]['title']) ? $items[$i]['title'] : SELF::CAPITAL_DIVISIONS[$key][$i];
        $form[$key]['items'][$i]['title'] = [
          '#type' => 'textfield',
          '#title' => 'Title',
          '#default_value' => $title,
          '#parents' => [$key, 'items', $i, 'title']
        ];
        
        $description = isset($items[$i]['description']) ? $items[$i]['description']['value'] : '';
        $form[$key]['items'][$i]['description'] = [
          '#type' => 'text_format',
          '#title' => 'Description',
          '#default_value' => $description,
          '#format' => 'full_html',
          '#parents' => [$key, 'items', $i, 'description']
        ];
       
      }

    }

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
    $config = $this->config('tb_gdmi.submission_results_subpages_settings');

    foreach (self::RESULT_PAGES as $page => $title) {
        $value = $form_state->getValue($page);
        $config->set($page, $value);
    }
     
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
