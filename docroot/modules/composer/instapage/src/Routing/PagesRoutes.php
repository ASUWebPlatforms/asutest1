<?php

namespace Drupal\instapage\Routing;

use Symfony\Component\Routing\Route;

/**
 * Defines dynamic routes.
 *
 * @package Drupal\instapage\Routing
 */
class PagesRoutes {

  /**
   * Dynamically create routes for configured paths.
   *
   * @return array
   *   Array of routes.
   */
  public function routes(): array {
    $routes = [];
    $config = \Drupal::config('instapage.pages');
    $pages = $config->get('instapage_pages');

    if ($pages) {
      foreach ($pages as $instapage_id => $path) {
        $route_key = 'instapage.pages.' . $instapage_id;
        $routes[$route_key] = new Route(
          $path,
          [
            '_controller' => '\Drupal\instapage\Controller\PageDisplayController::content',
            'instapage_id' => $instapage_id,
          ],
          [
            '_permission' => 'access content',
          ]
        );
      }
    }
    return $routes;
  }

}
