<?php

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function thunderbird_theme_suggestions_form_alter(array &$suggestions, array $variables) {
  $form_id = $variables['element']['#form_id'];
  if ($form_id === 'user_login_form' || $form_id === 'user_register_form') {
    $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
    if ($current_domain_id) {
      $suggestions[] = end($suggestions) . '__'  . $current_domain_id;
    }
  }
}

/**
 * Implements hook_theme_suggestions_alter().
 */
function thunderbird_theme_suggestions_input_alter(&$suggestions, $variables) {
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id !== 'gdmi')
    return;
    
  if ($variables['element']['#type'] === 'submit') {
    $webform = \Drupal::routeMatch()->getParameter('webform');
    if ($webform !== NULL) {
      $categories = $webform->get('categories');
      if (!empty($categories)) {
        $category = reset($categories);
        $suggestions[] = 'input__submit__' . str_replace(' ', '_', strtolower($category));
      }
    }
  }
}

/**
 * Implements hook_theme_suggestions_alter().
 */
function thunderbird_theme_suggestions_fieldset_alter(&$suggestions, $variables) {
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id !== 'gdmi')
    return;
 
  $webform = \Drupal::routeMatch()->getParameter('webform');
  if ($webform !== NULL) {
    $categories = $webform->get('categories');
    if (!empty($categories)) {
      $category = reset($categories);
      $suggestions[] = 'fieldset__' . str_replace(' ', '_', strtolower($category));
    }
  }
}

/**
 * Implements hook_theme_suggestions_alter().
 */
function thunderbird_theme_suggestions_table_alter(&$suggestions, $variables) {
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id !== 'gdmi')
    return;
 
  $webform = \Drupal::routeMatch()->getParameter('webform');
  if ($webform !== NULL) {
    $categories = $webform->get('categories');
    if (!empty($categories)) {
      $category = reset($categories);
      $suggestions[] = 'table__' . str_replace(' ', '_', strtolower($category));
    }
  }
}