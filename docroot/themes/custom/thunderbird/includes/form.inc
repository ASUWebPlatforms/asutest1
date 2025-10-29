<?php
/**
 * @file
 * Theme and preprocess functions for forms.
 */

/**
 * Implements hook_form_alter().
 */
function thunderbird_form_alter(&$form, &$form_state, $form_id) {
  switch ($form_id) {
    case 'layout_builder_configure_section':
      $form['layout_settings']['classes']['style']['#access'] = FALSE;
      break;
  }

  return $form;
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function thunderbird_form_views_exposed_form_alter(&$form, $form_state, $form_id) {
  if ($form['#id'] === 'views-exposed-form-newsroom-listing') {
    $form['search']['#placeholder'] = t('Search');
    $new_types_labels = ['news' => 'Thunderbird News', 'thunderbird_news' => 'Media Mentions'];
    $keys = array_keys($new_types_labels);
    foreach ($form['type']['#options'] as $key => $label) {
      if (in_array($key, $keys)) {
        $form['type']['#options'][$key] = $new_types_labels[$key];
      }
    }
  }

  $ajax_go_to_ids = ['views-exposed-form-newsroom-listing', 'views-exposed-form-events-block-1', 'views-exposed-form-faculty-block-1', 'views-exposed-form-insights-listing-cards'];
  if (in_array($form['#id'], $ajax_go_to_ids)) {
    $form['#attached']['library'][] = 'thunderbird/view_ajax_go_to';
    $form['#attached']['drupalSettings']['view_ajax_go_to'][] = $form['#id'];
  }
}