<?php

use Drupal\Core\Url;
use Drupal\responsive_background_image\ResponsiveBackgroundImage;

/**
* Implements hook_preprocess_HOOK() for content paragraphs.
*/
function thunderbird_preprocess_paragraph(&$variables) {
  $parent = $variables['elements']['#paragraph']->_referringItem;
  if (!$parent) {
    return;
  }
  $parent = $parent->getEntity();
  $template = $parent->get('type')->getValue()[0]['target_id'];
  $paragraph = $variables['paragraph'];
  if ($template == 'signposts') {
    $variation = $parent->get('field_signposts_variation')->value;
    $variationToLower = strtolower($variation);
    $signpostsVariation = str_replace(' ', '-', $variationToLower);
    $variables['signposts_variation'] = $signpostsVariation;
  }
  if ($template == 'blockquote') {
    $blockquote_variation = $parent->get('field_quote_layout')->value;
    $variables['blockquote_type'] = $blockquote_variation;

    if ($blockquote_variation == 'banner') {
      $img_field_name = ['field_media'];
      $responsive_field_name = 'quote_banner';
      responsiveBkgImg($variables, $paragraph, $img_field_name, $responsive_field_name);      
    }
    if ($blockquote_variation == 'fifty-fifty') {
      $img_field_name = ['field_media'];
      $responsive_field_name = 'blockquote_50_50';
      responsiveBkgImg($variables, $paragraph, $img_field_name, $responsive_field_name);      
    }
  }
  if ($template == 'flip_cards') {
    $img_field_name = ['field_image'];
    $responsive_field_name = 'flip_cards';
    responsiveBkgImg($variables, $paragraph, $img_field_name, $responsive_field_name);
  }

  if ($template == 'stats_carousel') {
    $reduce_font_size = $paragraph->get('field_reduce_heading_size')->value;
    $variables['headline_class'] = $reduce_font_size ? ' smaller' : '';
  }

  if ($template == 'hero') {
    if ($parent->hasField('field_gdmi_home')) {
      if ($parent->get('field_gdmi_home')->value === '1') {
        $route_name =  $variables['content']['field_cta_link'][0]['#url']->getRouteName();
        $variables['thun_icon'] = TRUE;
        $variables['has_icon'] = FALSE;
        $variables['button_action'] = FALSE;

        if (\Drupal::currentUser()->isAuthenticated()) {
          if ($route_name === 'user.register') {
            $variables['content']['field_cta_link'][0]['#title'] = 'Create a group';
            $variables['content']['field_cta_link'][0]['#url'] = Url::fromRoute('tb_gdmi.create_group');
          }

          if ($route_name === 'user.login') {
            $variables['content']['field_cta_link'][0]['#url'] = Url::fromRoute('tb_gdmi.dashboard_purchasing');
          }
        }

        if ($route_name === 'user.login') {
          $variables['content']['field_cta_link'][0]['#title'] = t('Or <span class="text-gold">Login</span>');
        }
      }
    }
  }

  if ($template == 'image_and_text_gdmi_block' || $template === 'signpost_w_o_image') {
    $variables['thun_icon'] = TRUE;
    $variables['has_icon'] = FALSE;
    $variables['button_action'] = FALSE;
  }
}

function responsiveBkgImg(&$variables, $paragraph, $img_field_name, $responsive_field_name) {
  $image_field = '';
  $value = NULL;
  $img_fields = $img_field_name;
  foreach ($img_fields as $img_field) {
    if (empty($value) && $paragraph->hasField($img_field)) {
      $value = $paragraph->get($img_field)->target_id;
      if ($value) {
       $image_field = $img_field;
      }
    }
  }
  if (!empty($value) && $image_field) {
    $css_class = 'paragraph-' . $paragraph->id();
    $variables['attributes']['class'][] = $css_class;
    $css_selector = '.' . $css_class;

    $style_tag = ResponsiveBackgroundImage::generateMediaQueries($css_selector, $paragraph, $image_field,$responsive_field_name);

    if ($style_tag) {
      $variables['#attached']['html_head'][]= $style_tag;
    }
  }
}