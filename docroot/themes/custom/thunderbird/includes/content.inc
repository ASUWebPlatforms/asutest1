<?php
/**
 * @file
 * Theme and preprocess functions for nodes.
 */

use Drupal\Component\Utility\Html;
use Drupal\Core\Language\LanguageInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Drupal\responsive_background_image\ResponsiveBackgroundImage;

/**
 * Implements hook_preprocess_node().
 */
function thunderbird_preprocess_node(&$variables) {
  $node = $variables['node'];
  $bundle = $node->bundle();
  $view_mode = $variables['view_mode'];
  $variables['base_path'] = base_path();

  if ($bundle == 'person_bio') {
    $expertise_areas = [];

    if (isset($variables['elements']['field_expertise'])) {
      foreach ($variables['elements']['field_expertise'] as $index => $expertise) {
        if (is_numeric($index)) {
          $expertise_areas[] = $expertise['#plain_text'];
        }
      }
    }

    $variables['expertise_areas'] = $expertise_areas;

    $faculty_default_img = theme_get_setting('faculty_default_img', 'thunderbird');

    // Use default image in card if faculty member has no image set.
    if (!empty($faculty_default_img) && !isset($variables['content']['field_image'][0])) {
      $media = $faculty_default_img ? Media::load($faculty_default_img) : NULL;
      if ($media) {
        $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');
        $build = $view_builder->view($media, 'signpost_2_columns');
        $variables['content']['field_image'][0] = $build;
      }
    }
  }

  if ($view_mode == 'hero' || $bundle == 'person_bio') {
    $image_field = '';
    $value = NULL;
    $entity = $node;

    $img_fields = ['field_image', 'field_article_image'];
    foreach ($img_fields as $img_field) {
      if (empty($value) && $node->hasField($img_field)) {
        $value = $node->get($img_field)->target_id;
        if ($value) {
          $image_field = $img_field;
        }
      }
    }

    // For Faculty with no image set, get the file from the default image.
    if (!$value && $bundle == 'person_bio' && isset($media)) {
      $fid = $media->field_media_image->target_id;
      $file = File::load($fid);
      $entity = $file;
      $value = TRUE;
    }

    if (!empty($value)) {
      $nid = $node->id();
      $css_class = 'hero-node-' . $nid;
      $variables['attributes']['class'][] = $css_class;
      if ($view_mode == 'hero') {
        $css_selector = '.' . $css_class . '.hero-wrapper';
      }
      else {
        $css_selector = '.' . $css_class . ' .hero-wrapper';
      }

      $style_tag = ResponsiveBackgroundImage::generateMediaQueries($css_selector, $entity, $image_field, 'hero');

      if ($style_tag) {
        $variables['#attached']['html_head'][] = $style_tag;
      }
    }
  }
  elseif ($view_mode == 'card' && $bundle == 'event') {
    $event_date_from = $event_time_from = $event_time_to = '';
    $event_date = $node->field_event_date->date;
    $timezone_id = $node->field_timezone_id->value;
    
    if ($event_date) {
      // If the timezone is provided, use it to adjust the event time.
      if ($timezone_id) {
        try {
          $event_date->setTimezone(new \DateTimeZone($timezone_id));
        }
        catch(Exception $e) {
          // Timezone identifier was not valid.
        }
      }
      $event_date_from = $event_date->format('m/d/y');
      $event_time_from = $event_date->format('g:i a');
    }
    $end_date = $node->field_end_date->date;
    if ($end_date) {
      if ($timezone_id) {
        try {
          $end_date->setTimezone(new \DateTimeZone($timezone_id));
        }
        catch(Exception $e) {
          // Timezone identifier was not valid.
        }
      }
      $event_time_to = $end_date->format('g:i a');
    }

    $variables['content']['event_date'] = $event_date_from;
    $variables['content']['event_time_from'] = $event_time_from;
    $variables['content']['event_time_to'] = $event_time_to;
  }
  elseif ($view_mode == 'grid' && $bundle == 'insight') {
    $image_field = '';

    $img_fields = ['field_image', 'field_article_image'];
    foreach ($img_fields as $img_field) {
      if ($node->hasField($img_field)) {
        $value = $node->get($img_field)->target_id;
        if ($value) {
          $image_field = $img_field;
          break;
        }
      }
    }

    if ($image_field) {
      $nid = $node->id();
      $css_class = 'node-' . $nid;
      $variables['attributes']['class'][] = $css_class;
      $css_selector = '.insight.' . $css_class . ' .insight-image';

      $style_tag = ResponsiveBackgroundImage::generateMediaQueries($css_selector, $node, $image_field, 'insights_grid');

      if ($style_tag) {
        $variables['bg_image'] = $style_tag;
      }
    }

    $variables['#cache']['contexts'][] = 'url.path';
  }

  if ($bundle == 'certificate') {
    $delivery = $node->get('field_certificate_type')->target_id;
    if ($delivery) {
      $entity_type_manager = \Drupal::entityTypeManager();

      $delivery_term = $entity_type_manager->getStorage('taxonomy_term')->load($delivery);

      if ($delivery_term) {
        $delivery_label = str_replace(' ', '', strtolower($delivery_term->label()));
        $variables['attributes']['class'][] = 'delivery--' . Html::cleanCssIdentifier($delivery_label);
      }
    }
    $variables['#attached']['library'] = 'thunderbird/certificate-scroll-registration';
  }

  // Add dir to article attributes if needed.
  if ($node->language()->getId() != LanguageInterface::LANGCODE_NOT_SPECIFIED) {
    $interface_language = \Drupal::languageManager()->getCurrentLanguage();
    $node_language = $node->language();

    // Add markup to identify the language.
    $variables['attributes']['lang'] = $node_language->getId();
    // Add direction information as well.
    $variables['attributes']['dir'] = $node_language->getDirection();
  }

}
