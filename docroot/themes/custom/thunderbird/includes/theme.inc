<?php

/**
 * @file
 * Custom theme hooks.
 */

/**
 * Implements template_preprocess_html().
 */
function thunderbird_preprocess_html(&$variables) {
  // Add Google fonts preconnect links to head.

  $fonts_api = [
    '#tag' => 'link',
    '#attributes' => [
      'rel' => 'preconnect',
      'href' => 'https://fonts.googleapis.com',
    ],
  ];

  $fonts_gstatic = [
    '#tag' => 'link',
    '#attributes' => [
      'rel' => 'preconnect',
      'href' => 'https://fonts.gstatic.com',
      'crossorigin' => TRUE,
    ],
  ];

  $variables['page']['#attached']['html_head'][] = [$fonts_api, 'google-fonts-api'];
  $variables['page']['#attached']['html_head'][] = [$fonts_gstatic, 'google-fonts-gstatic'];


  // Add class to body based on current path.

  // Get the current path.
  $current_path = \Drupal::service('path.current')->getPath();
  $internal_path = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);
  if ($internal_path) {
    // Get first part of path and add class.
    $internal_path_parts = explode('/', $internal_path);
    if (isset($internal_path_parts[1])) {
      $variables['attributes']['class'][] = 'path--' . $internal_path_parts[1];
    }
  }
}
