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
 * Handles deleting instapage pages.
 *
 * @package Drupal\instapage\Form
 */
class PageDeleteForm extends FormBase {

  /**
   * Instapage pages config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * Module settings config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * Instapage page label.
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
  protected $id;

  /**
   * Route builder service.
   *
   * @var \Drupal\Core\ProxyClass\Routing\RouteBuilder
   */
  protected RouteBuilder $routeBuilder;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'instapage_delete_page';
  }

  /**
   * PageDeleteForm constructor.
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
    $this->id = $request->getCurrentRequest()->get('instapage_id');
    $this->routeBuilder = $routeBuilder;
    $this->api = $api;
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
    $labels = $this->pagesConfig->get('page_labels');
    $this->label = (array_key_exists($this->id, $labels) ? $labels[$this->id] : '');
    $form['label'] = [
      '#type' => 'item',
      '#markup' => $this->t('Are you sure you want to delete the path and unpublish the page @label?', ['@label' => $this->label]),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
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
        'callback' => 'Drupal\instapage\Form\PageDeleteForm::closeModal',
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
    if ($op == 'submit') {
      $token = $this->config->get('instapage_user_token');

      // Unpublish the page throught the API.
      $this->api->editPage($this->id, '', $token, 0);

      // Rebuild the route cache to instantly apply path changes.
      $this->routeBuilder->rebuild();

      // Set the message and redirect back to the pages form.
      $this->messenger()->addStatus($this->t('Path for @label has been removed.', ['@label' => $this->label]));
      $form_state->setRedirect('instapage.landing_pages');
    }
  }

}
