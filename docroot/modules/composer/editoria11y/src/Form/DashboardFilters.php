<?php

namespace Drupal\editoria11y\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class to apply the dashboard filters..
 */
class DashboardFilters extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editoria11y_form_dashboard';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $dashboard = \Drupal::service('editoria11y.dashboard');

    $counter = 1;
    $entity_selects = $dashboard->getEntityTypes();
    $entity_types = ['all' => $this->t('Select...')];
    foreach ($entity_selects as $type) {
      $entity_types[$type->entity_type] = $type->entity_type;
      $counter++;
    }

    $langs = $dashboard->getLanguages();
    $multilang = $langs['count'] > 1 ? TRUE : FALSE;

    $open = FALSE;

    // Open by default if any fields are not blank OR "all.".
    // @todo redo need to check each individually.
    if (
          !empty($form_state->getValue('title')) |
          !empty($form_state->getValue('url')) |
          (!empty($form_state->getValue('result_name') && $form_state->getValue('result_name') !== 'all')) |
          (!empty($form_state->getValue('type') && $form_state->getValue('type') !== 'all')) |
          ($multilang && !empty($form_state->getValue('lang') && $form_state->getValue('lang') !== 'all'))
          ) {
      $open = TRUE;
    }
    $form['filter'] = [
      '#title' => $this->t('Filter results'),
      '#type' => 'details',
      '#open' => $open,
    ];
    $form['filter']['title'] = [
      '#title'         => $this->t('Page title contains'),
      '#type'          => 'textfield',
    ];
    $form['filter']['url'] = [
      '#title'         => $this->t('Url contains'),
      '#type'          => 'textfield',
    ];
    $form['filter']['type'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Page type'),
      '#options' => $entity_types,
    ];

    if ($multilang) {
      $langselects = [
        'all' => 'all',
      ];
      foreach ($langs['languages'] as $lang) {
        $langselects[$lang->page_language] = $lang->page_language;
      }
      $form['filter']['lang'] = [
        '#title' => t('Language'),
        '#type' => 'select',
        '#options' => $langselects,
      ];
    }
    $form['filter']['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    /*if ($form_state->getValue('fname') == "") {
    $form_state->setErrorByName('from',
    $this->t('You must enter a valid first name.'
    ));
    } elseif ($form_state->getValue('marks') == "") {
    $form_state->setErrorByName('marks',
    $this->t('You must enter a valid to marks.'
    ));
    }*/
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // $field = $form_state->getValues();
    // $url = Url::fromRoute('editoria11y.reports_pages',
    // ['title' => $form_state->getValue('title'),
    // 'url' => $form_state->getValue('url')]);
    /*$url = Url::fromRoute('editoria11y.reports_pages')
    ->setRouteParameter('title', $form_state->getValue('title'))
    ->setRouteParameter('url', $form_state->getValue('url'));*/
    // $form_state->setRedirectUrl($url);
  }

}
