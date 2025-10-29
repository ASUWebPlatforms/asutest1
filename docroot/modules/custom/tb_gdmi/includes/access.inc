<?php
/**
 * @file
 * GDMI access functions .
 */

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Implements hook_user_login().
 */
function tb_gdmi_user_login(AccountInterface $account) {
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();

  if ($current_domain_id === 'gdmi') {

    $current_route_name = \Drupal::routeMatch()->getRouteName();
    if (in_array($current_route_name, ['user.reset.login'], TRUE)) {
      return;
    }

    $available_assessment = \Drupal::service('tb_gdmi.gdmi_groups')->getAvailableAssessment($account);
    $orders =  \Drupal::entityTypeManager()->getStorage('commerce_order')->loadByProperties([
      'state' => 'completed',
      'uid' => $account->id(),
      'type' => 'default'
    ]);

    if ($available_assessment === NULL && empty($orders)) {
      $url = Url::fromUserInput('/dashboard/purchasing');
      \Drupal::request()->query->set('destination', $url->toString());
    }

    if ($available_assessment !== NULL) {
      $url = Url::fromUserInput('/dashboard');
      \Drupal::request()->query->set('destination', $url->toString());
    }

    if ($available_assessment === NULL && !empty($orders)) {
      $url = Url::fromUserInput('/dashboard/groups');
      \Drupal::request()->query->set('destination', $url->toString());
    }
  }
}
