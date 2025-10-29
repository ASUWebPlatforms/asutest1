<?php

namespace Drupal\instapage\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Drupal\instapage\ApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles creation of instapage pages.
 *
 * @package Drupal\instapage\Form
 */
class PageNewForm extends FormBase {

  /**
   * Instapage api service.
   *
   * @var \Drupal\instapage\ApiInterface
   */
  protected ApiInterface $api;

  /**
   * Instapage pages config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * Module settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Route builder service.
   *
   * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected RouteBuilder $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'instapage_new_page';
  }

  /**
   * PageNewForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\instapage\ApiInterface $api
   *   Instapage api service.
   * @param \Drupal\Core\ProxyClass\Routing\RouteBuilder $routeBuilder
   *   Route builder service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ApiInterface $api, RouteBuilder $routeBuilder) {
    $this->pagesConfig = $config_factory->getEditable('instapage.pages');
    $this->config = $config_factory->getEditable('instapage.settings');
    $this->api = $api;
    $this->routeBuilder = $routeBuilder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('instapage.api'),
      $container->get('router.builder')
    );
  }

  /**
   * Get available pages. If subaccount is provided, return only pages from it.
   *
   * @param string|null $subAccount
   *   Subaccount name.
   *
   * @return array|mixed|null
   *   Return page labels.
   */
  private function getPages(string $subAccount = NULL) {
    $pageLabels = $this->pagesConfig->get('page_labels');
    $pages = $this->pagesConfig->get('instapage_pages');

    // In the dropdown show only pages that don't have a path set.
    if ($pageLabels && $pages) {
      foreach ($pages as $i => $item) {
        if (array_key_exists($i, $pageLabels)) {
          unset($pageLabels[$i]);
        }
      }
    }

    if ($subAccount) {
      $pageLabels = array_filter($pageLabels, function ($page) use ($subAccount) {
        return str_contains($page, $subAccount);
      });
    }

    return $pageLabels;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $subAccount = $this->pagesConfig->get('instapage_subaccounts');
    $subAccount[0] = $this->t('All workspaces');
    ksort($subAccount);

    $selectedSubAccount = $form_state->getValue('subaccount') ?? 0;
    $pageLabels = $this->getPages(
      $selectedSubAccount > 0 ? $subAccount[$selectedSubAccount] : NULL
    );

    $form['subaccount'] = [
      '#type' => 'select',
      '#title' => $this->t('Workspace'),
      '#required' => TRUE,
      '#options' => $subAccount,
      '#default_value' => $selectedSubAccount,
      '#ajax' => [
        'callback' => '::ajaxUpdatePageSelect',
        'disable-refocus' => FALSE,
        'event' => 'change',
        'wrapper' => 'instapage-page-select',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Retrieving pages...'),
        ],
      ],
    ];

    $form['page'] = [
      '#type' => 'select',
      '#title' => $this->t('Page'),
      '#required' => TRUE,
      '#options' => $pageLabels,
      '#prefix' => '<div id="instapage-page-select">',
      '#suffix' => '</div>',
      '#validated' => TRUE,
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#required' => TRUE,
      '#description' => $this->t('Without leading forward slash.'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save'),
      '#button_type' => 'primary',
    ];
    $form['cancel'] = [
      '#type' => 'button',
      '#limit_validation_errors' => [],
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => [
          'btn',
        ],
      ],
      '#ajax' => [
        'callback' => 'Drupal\instapage\Form\PageNewForm::closeModal',
        'event' => 'click',
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback to update page select options.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Form structure.
   */
  public function ajaxUpdatePageSelect(array &$form, FormStateInterface $form_state): AjaxResponse {
    $response = new AjaxResponse();

    $subAccount = $form_state->getValue('subaccount') ?? 0;
    $pageLabels = $this->getPages(
      $subAccount > 0 ? $form['subaccount']['#options'][$subAccount] : NULL
    );

    // Add select option to "all workspaces" and empty pages.
    if ($subAccount == 0 || empty($pageLabels)) {
      $selectOption = array_diff($form['page']['#options'], $pageLabels);
      $form['page']['#options'] = $selectOption + $pageLabels;
    }
    else {
      $form['page']['#options'] = $pageLabels;
    }

    $response->addCommand(new ReplaceCommand('#instapage-page-select', $form['page']));
    return $response;
  }

  /**
   * Closes modal dialog.
   *
   * @param array $form
   *   Form structure.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Form state.
   *
   * @return \Drupal\Core\Ajax\AjaxResponse
   *   Ajax response.
   */
  public static function closeModal(array $form, FormStateInterface $form_state): AjaxResponse {
    $form_state->setRebuild(FALSE);
    $response = new AjaxResponse();
    $response->addCommand(new CloseDialogCommand());
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $op = $trigger['#parents'][0];
    if ($op == 'submit') {
      $id = $form_state->getValue('page');
      $path = $form_state->getValue('path');
      $token = $this->config->get('instapage_user_token');

      // Send the edit command to the server.
      $this->api->editPage($id, $path, $token);

      // Rebuild the route cache to instantly apply path changes.
      $this->routeBuilder->rebuild();

      // Set the message and redirect back to the pages form.
      $labels = $this->pagesConfig->get('page_labels');
      $label = (array_key_exists($id, $labels) ? $labels[$id] : '');
      $this->messenger()->addStatus($this->t('Path for @label has been saved.', ['@label' => $label]));
      $form_state->setRedirect('instapage.landing_pages');
    }
  }

}
