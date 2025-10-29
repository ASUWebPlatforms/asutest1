<?php

namespace Drupal\instapage\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\RouteMatch;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Checks form access on Instapage page forms.
 */
class FormsAccessCheck extends ControllerBase {

  /**
   * Settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $settingsConfig;

  /**
   * Pages config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * FormsAccessCheck constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   ConfigFactory service.
   */
  public function __construct(ConfigFactory $config) {
    $this->settingsConfig = $config->getEditable('instapage.settings');
    $this->pagesConfig = $config->getEditable('instapage.pages');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * Checks access for given route.
   *
   * @param \Drupal\Core\Routing\RouteMatch $routeMatch
   *   Current route.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatch $routeMatch) {
    $instapageUserId = trim($this->settingsConfig->get('instapage_user_id'));
    $instapageToken = trim($this->settingsConfig->get('instapage_user_token'));
    if ($instapageUserId === '' || $instapageToken === '') {
      return AccessResult::forbidden();
    }

    switch ($routeMatch->getRouteName()) {
      case 'instapage.page_new':
        return AccessResult::allowed();

      default:
        $pageId = $routeMatch->getParameter('instapage_id');
        $pages = $this->pagesConfig->get('instapage_pages');
        return AccessResult::allowedIf($pages && $pageId && array_key_exists($pageId, $pages));
    }
  }

}
