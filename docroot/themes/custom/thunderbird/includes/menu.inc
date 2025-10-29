<?php
/**
 * @file
 * Theme and preprocess functions for menus.
 */

/**
 * Implements template_preprocess_links__dropbutton__dropdown_language().
 */
function thunderbird_preprocess_links__dropbutton__dropdown_language(&$variables) {

  // Rebuild first option removed by Radix preprocess.
  if (isset($variables['button'])) {
    $button = [
      'link' => $variables['button'],
      'text' => $variables['button']['#title'],
    ];

    array_unshift($variables['links'], $button);
  }

}
