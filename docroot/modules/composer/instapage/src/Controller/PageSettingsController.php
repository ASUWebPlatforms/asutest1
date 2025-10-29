<?php

namespace Drupal\instapage\Controller;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\instapage\ApiInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Handles instapage page settings.
 *
 * @package Drupal\instapage\Controller
 */
class PageSettingsController extends ControllerBase {

  /**
   * Instapage api service.
   *
   * @var \Drupal\instapage\ApiInterface
   */
  protected ApiInterface $api;

  /**
   * Instapage settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Instapage pages config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * PaseSettingsController constructor.
   *
   * @param \Drupal\instapage\ApiInterface $api
   *   Instapage api service.
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Renderer service.
   */
  public function __construct(ApiInterface $api, ConfigFactory $config, RendererInterface $renderer) {
    $this->api = $api;
    $this->config = $config->getEditable('instapage.settings');
    $this->pagesConfig = $config->getEditable('instapage.pages');
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('instapage.api'),
      $container->get('config.factory'),
      $container->get('renderer')
    );
  }

  /**
   * Builds the page content.
   *
   * @return array
   *   Render array.
   */
  public function content(): array {
    $token = $this->config->get('instapage_user_token');
    $subAccounts = $this->api->getSubAccounts($token);
    global $base_url;

    $build = [
      '#type' => 'markup',
    ];

    // If the user is not logged in.
    if (!$token) {
      $build['#markup'] = '<p>' . $this->t("You don't have the Instapage account setup yet.") . '</p>';
      $build['#markup'] .= '<p>' . $this->t('Please connect your account <a href="@link">here</a>.', ['@link' => Url::fromRoute('instapage.settings')->toString()]) . '</p>';
      return $build;
    }

    $table = [
      '#type' => 'table',
      '#header' => [
        $this->t('Title'),
        $this->t('Sub Account'),
        $this->t('Path'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('There are no items yet.'),
    ];

    $site_settings = Url::fromRoute('system.site_information_settings')->toString();
    $markup = '<p>' . $this->t("Below is a list of Instapage pages connected to your website. Click 'Add new page' to add another one.") . '</p>';
    $markup .= '<p>' . $this->t('If you want to show Instapage as a front page, choose a path (for example: front) and then change default front page path <a href="@link">here</a>.', ['@link' => $site_settings]) . '</p>';

    // Fetch available pages from the API.
    $result = $this->api->getPageList($token);
    $pages = $this->pagesConfig->get('instapage_pages');
    $rows = [];

    $link_attributes = [
      'class' => ['use-ajax', 'button'],
      'data-dialog-type' => 'modal',
      'data-dialog-options' => Json::encode([
        'width' => 300,
      ]),
    ];

    // Process all the pages returned from the API.
    if (isset($result->data) && !empty($result->data) || $pages) {
      if (is_array($pages)) {
        foreach ($pages as $id => $path) {
          foreach ($result->data as $item) {
            if ($item->id == $id) {

              // Dropdown links.
              $links = [
                '#type' => 'dropbutton',
                '#links' => [
                  'edit' => [
                    'title' => $this->t('Edit'),
                    'url' => Url::fromRoute('instapage.page_edit', ['instapage_id' => $id]),
                    'attributes' => $link_attributes,
                  ],
                  'delete' => [
                    'title' => $this->t('Delete'),
                    'url' => Url::fromRoute('instapage.page_delete', ['instapage_id' => $id]),
                    'attributes' => $link_attributes,
                  ],
                ],
              ];
              $op_links = $this->renderer->render($links);

              // The current url alias of the page.
              $path_url = Url::fromUri('internal:/' . $path, ['attributes' => ['target' => '_blank']]);
              $path_link = Link::fromTextAndUrl($base_url . '/' . $path, $path_url);

              $insert = [
                $item->title,
                $subAccounts[$item->subaccount],
                $path_link,
                $op_links,
              ];
              $rows[] = $insert;
              break;
            }
          }
        }
      }
    }
    else {
      if (isset($result->error) && $result->error) {
        $this->messenger()->addError($this->t('Connection error. Message from Instapage: @msg', ['@msg' => $result->message]));
      }
      elseif (isset($result->error) && !$result->error && empty($result->data)) {
        $this->messenger()->addError($this->t('Please add a page on the Instapage app before continuing.'));
      }
      return $build;
    }

    // Render the rable.
    $table['#rows'] = $rows;
    $table_render = $this->renderer->render($table);

    $add_new = [
      '#type' => 'link',
      '#attributes' => $link_attributes,
      '#title' => $this->t('Add new page'),
      '#url' => Url::fromRoute('instapage.page_new'),
    ];

    $add_new['#attributes']['class'][] = 'instapage-add-new';

    // Render the 'Add new page' link.
    $add_new_render = $this->renderer->render($add_new);

    $build['#markup'] = $markup . $add_new_render . $table_render;
    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $build;
  }

}
