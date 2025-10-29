<?php

namespace Drupal\tb_gdmi\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Adds a custom access check to user routes.
 */
class GdmiRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('commerce_cart.page')) {
      $route->setRequirement('_cart_access_check', 'TRUE');
    }

    if ($route = $collection->get('entity.webform.canonical')) {
      $route->setRequirement('_assessment_access_check', 'TRUE');
    }
    if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
      return;
    }
    $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
    if ($current_domain_id === 'gdmi') {

      if ($route = $collection->get('entity.user.edit_form')) {
        $requirements = $route->getRequirements();
        unset($requirements['_entity_access']);
        $route->setRequirements($requirements);
        $route->setRequirement('_group_members_profile_access_check', 'TRUE');
      }

      if ($route = $collection->get('entity.user.canonical')) {
        $requirements = $route->getRequirements();
        unset($requirements['_entity_access']);
        $route->setRequirements($requirements);
        $route->setRequirement('_group_members_profile_access_check', 'TRUE');
      }

    }

  }

}
