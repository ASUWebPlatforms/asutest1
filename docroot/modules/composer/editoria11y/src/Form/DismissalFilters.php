<?php

namespace Drupal\editoria11y\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

/**
 * Class DashboardFilters.
 */
class DismissalFilters extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'editoria11y_form_dismissal_filters';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $dashboard = \Drupal::service('editoria11y.dashboard');

    $counter = 1;
    $result_selects = $dashboard->getResultNames();
    $result_names = ['all' => $this->t('Select...')];
    foreach ($result_selects as $issue) {
      $result_names[$issue->result_name] = $issue->result_name;
      $counter++;
    }
    $counter = 1;
    $entity_selects = $dashboard->getEntityTypes();
    $entity_types = ['all' => $this->t('Select...')];
    foreach ($entity_selects as $type) {
      $entity_types[$type->entity_type] = $type->entity_type;
      $counter++;
    }

    $user_selects = $dashboard->getUserIds();
    $users = ['all' => $this->t('Select...')];
    foreach ($user_selects as $uid) {
      $user = User::load($uid->uid);
      $name = $user->getDisplayName();
      $users[$uid->uid] = $name;
    }

    $langs = $dashboard->getLanguages();
    $multilang = $langs['count'] > 1 ? TRUE : FALSE;

    $open = "";
    // Open by default if any fields have text or !== their matching default
    // values.
    if (
          !empty($form_state->getValue('title')) |
          !empty($form_state->getValue('url')) |
          (!empty($form_state->getValue('result') && $form_state->getValue('result') !== 'all')) |
          (!empty($form_state->getValue('type') && $form_state->getValue('type') !== 'all')) |
          (!empty($form_state->getValue('uid') && $form_state->getValue('uid') !== 'all')) |
          ($multilang && !empty($form_state->getValue('lang') && $form_state->getValue('lang') !== 'all')) |
          (!empty($form_state->getValue('status')) && $form_state->getValue('status') !== 'all') |
          ($form_state->getValue('stale') === '0' | $form_state->getValue('stale') === '1')
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
    $form['filter']['result'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Issue type'),
      '#options' => $result_names,
    ];
    $form['filter']['uid'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('User'),
      '#options' => $users,
    ];
    $form['filter']['status'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Marked'),
      '#options' => [
        'all' => $this->t("Select..."),
        'hide' => $this->t("hide"),
        'ok' => $this->t('ok'),
      ],
    ];
    $form['filter']['stale'] = [
      '#type' => 'select',
      '#title' => $this
        ->t('Still on page'),
      '#options' => [
        'all' => $this->t("Select..."),
        '0' => $this->t("yes"),
        '1' => $this->t('no'),
      ],
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
    $this->t('You must enter a valid first name.
    '));
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
    // 'url' => $form_state->getValue('url')
    // ]);
    /*$url = Url::fromRoute('editoria11y.reports_pages')
    ->setRouteParameter('title', $form_state->getValue('title'))
    ->setRouteParameter('url', $form_state->getValue('url'));*/
    // $form_state->setRedirectUrl($url);
  }

}
