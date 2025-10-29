<?php

namespace Drupal\instapage\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\ProxyClass\Routing\RouteBuilder;
use Drupal\instapage\ApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Handles editing instapage pages.
 *
 * @package Drupal\instapage\Form
 */
class PageEditForm extends FormBase {

  /**
   * Module settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Instapage label.
   *
   * @var string
   */
  protected string $label;

  /**
   * Instapage api service.
   *
   * @var \Drupal\instapage\ApiInterface
   */
  protected ApiInterface $api;

  /**
   * Instapage id.
   *
   * @var string
   */
  protected string $id;

  /**
   * Route builder service.
   *
   * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected RouteBuilder $routeBuilder;

  /**
   * Instapage pages config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'instapage_edit_page';
  }

  /**
   * PageEditForm constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory service.
   * @param \Drupal\Core\ProxyClass\Routing\RouteBuilder $routeBuilder
   *   Route builder service.
   * @param \Drupal\instapage\ApiInterface $api
   *   Instapage api service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack service.
   */
  public function __construct(ConfigFactory $config, RouteBuilder $routeBuilder, ApiInterface $api, RequestStack $request) {
    $this->pagesConfig = $config->getEditable('instapage.pages');
    $this->config = $config->get('instapage.settings');
    $this->routeBuilder = $routeBuilder;
    $this->api = $api;
    $this->id = $request->getCurrentRequest()->get('instapage_id');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('router.builder'),
      $container->get('instapage.api'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $pages = $this->pagesConfig->get('instapage_pages');
    $labels = $this->pagesConfig->get('page_labels');
    $this->label = (array_key_exists($this->id, $labels) ? $labels[$this->id] : '');

    $form['label'] = [
      '#type' => 'item',
      '#title' => 'Page Label',
      '#markup' => $this->label,
    ];
    $form['path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Path'),
      '#required' => TRUE,
      '#default_value' => ($pages[$this->id] ?? ''),
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
        'callback' => 'Drupal\instapage\Form\PageEditForm::closeModal',
        'event' => 'click',
      ],
    ];
    return $form;
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
    // 'Save' clicked.
    if ($op == 'submit') {
      $path = $form_state->getValue('path');
      $token = $this->config->get('instapage_user_token');

      // Send the changes through the API.
      $this->api->editPage($this->id, $path, $token);

      // Rebuild the route cache to instantly apply path changes.
      $this->routeBuilder->rebuild();

      // Set the message and redirect back to the pages form.
      $this->messenger()->addStatus($this->t('Path for @label has been saved.', ['@label' => $this->label]));
      $form_state->setRedirect('instapage.landing_pages');
    }
  }

}
