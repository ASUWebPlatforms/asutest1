<?php
/**
 * @file
 * Contains tb_gdmi suggestions hooks.
 */

use Drupal\webform\Entity\Webform;

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function tb_gdmi_theme_suggestions_page_alter(array &$suggestions, array $variables) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();

  if ($current_domain_id !== 'gdmi')
    return; 

  $current_route_name = \Drupal::routeMatch()->getRouteName();
  $routes = ['user.login', 'user.register', 'entity.user.canonical'];

  if (in_array($current_route_name, $routes)) {
    if ($current_domain_id) {
      $suggestions[] = 'page__user__'  . $current_domain_id; 
    }
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function tb_gdmi_theme_suggestions_webform_alter(array &$suggestions, array $variables) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();

  if ($current_domain_id !== 'gdmi')
    return; 

  $webform = Webform::load($variables['element']['#webform_id']);
  $categories = $webform->get('categories');
  if (!empty($categories)) {
    $category = reset($categories);
    $suggestions[] = 'webform__'  . str_replace(' ', '_', strtolower($category));
  }

}
  
/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function tb_gdmi_theme_suggestions_user_alter(array &$suggestions, array $variables) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id) {
    $suggestions[] = 'user__' . $current_domain_id; 
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function tb_gdmi_theme_suggestions_form_alter(array &$suggestions, array $variables) {
  if ($variables['element']['#form_id'] === 'user_form') {
    if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
      return;
    }
    $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
    if ($current_domain_id) {
      $suggestions[] = 'form__' . $variables['element']['#form_id'] . '__' . $current_domain_id; 
    }
  }
}

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function tb_gdmi_theme_suggestions_commerce_checkout_form_alter(array &$suggestions, array $variables) {
  if ($variables['form']['#step_id'] === 'review' || $variables['form']['#step_id'] === 'order_information' || $variables['form']['#step_id'] === 'group') {
    $suggestions[] = 'commerce_checkout_form__' . $variables['form']['#step_id']; 
  }
}

