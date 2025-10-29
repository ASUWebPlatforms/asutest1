<?php

namespace Drupal\tb_gdmi\Plugin\Menu;

use Drupal\Core\Menu\MenuLinkDefault;

/**
 * Represents a menu link for the gdmi results menu.
 */
class MenuLinkResultsSubmission extends MenuLinkDefault {

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return ['url.path'];
  }
  /**
   * {@inheritdoc}
   */
  public function getRouteParameters() {
    $original_params = $this->pluginDefinition['route_parameters'] ?: [];
    $last_submission = \Drupal::service('tb_gdmi.gdmi_utils')->getLastUserSubmission(\Drupal::currentUser()->id());
    $submission_id = $last_submission !== NULL ? $last_submission->id() : $original_params['submission_id'];
    
    $param_sid = \Drupal::service('current_route_match')->getParameter('submission_id');
    if ($param_sid) {
      $submission_id = $param_sid;
    }

    $params = ['submission_id' => $submission_id];

    if (isset($original_params['capital_name'])) {
      $params['capital_name'] = $original_params['capital_name'];
    }

    return $params;
  }

  /**
   * Disable menu link if a real dynamic parameter can't be determined.
   */
  public function isEnabled() {
    $last_submission = \Drupal::service('tb_gdmi.gdmi_utils')->getLastUserSubmission(\Drupal::currentUser()->id());
    if ($last_submission === NULL) {
      return FALSE;
    }

    $route_name = \Drupal::service('current_route_match')->getRouteName();
    if (!($route_name === 'tb_gdmi.dashboard_results' || $route_name === 'tb_gdmi.dashboard_capital_results')) {
      return FALSE;
    }
    
    return parent::isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
