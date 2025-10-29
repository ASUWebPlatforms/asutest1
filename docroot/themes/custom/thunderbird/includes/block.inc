<?php
/**
 * @file
 * Theme and preprocess functions for blocks.
 */

use Drupal\block\Entity\Block;

use Drupal\block_content\BlockContentInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;
use Drupal\Core\Language\LanguageInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\media\Entity\Media;
use Drupal\node\NodeInterface;
use Drupal\responsive_background_image\ResponsiveBackgroundImage;



/**
* Implements hook_preprocess_HOOK() for content blocks.
*/
function thunderbird_preprocess_block(&$variables) {
  $block_type = $variables['base_plugin_id'];
  if ($block_type == 'inline_block') {
    $block_type = $variables['derivative_plugin_id'];
  }
  else if ($block_type == 'block_content') {
    $block_type = $variables['bundle'];
  }

  $block_content = FALSE;
  $variables['heading_level'] = 'h2';
  $variables['heading_style'] = $variables['heading_level'];

  $content = $variables['elements']['content']; 
  if (isset($content['#block_content']) && $content['#block_content'] instanceof BlockContentInterface) {
    $block_content = $content['#block_content'];
    if ($block_content->hasField('field_heading_level') && $heading_level = $block_content->get('field_heading_level')->value) {
      $variables['heading_level'] = $heading_level;
    }
    if ($block_content->hasField('field_heading_style') && $heading_style = $block_content->get('field_heading_style')->value) {
      $variables['heading_style'] = $heading_style;
    }
  }

  $variables['layout_mode'] = FALSE;

  $route_name = \Drupal::routeMatch()->getRouteName();
  if ($route_name == 'layout_builder.overrides.node.view') {
    $variables['layout_mode'] = TRUE;
  }

  switch ($block_type) {
    case 'signposts':
      $variables['signposts_variation'] = $variables['content']['field_signposts_variation'][0]["#markup"];
      
      break;

    case 'card_selection':
      if ($block_content) {
        $variables['columns'] = $block_content->get('field_columns')->value;
        $variables['content_position'] = $block_content->get('field_content_position_cards')->value;

        $hide_images = $block_content->get('field_hide_images')->value;
        if ($hide_images) {
          $variables['hide_images'] = TRUE;
        }

        $card_display = $block_content->get('field_card_display')->value;
        $variables['card_display'] = strpos($card_display, 'horizontal') !== FALSE
          ? 'horizontal' : 'vertical';
        $variables['card_heading_level'] = $block_content->get('field_cards_heading_level')->value;


        $items_to_display = 0;
        if ($block_content->hasField('field_items_to_display')) {
          $items_to_display = $block_content->get('field_items_to_display')->value;
        }

        $new_items = $selected_nids = [];
        foreach ($variables['content']['field_items'] as $key => $value) {
          if (is_numeric($key)) {
            $new_items[] = $value;
            $selected_nids[] = $value['#node']->id();
          }
        }

        $items_count = count($new_items);
        // If we have fewer items than needed.
        if ($items_count < $items_to_display) {
          // The type of content to display.
          $content_type = $block_content->get('field_content_type')->value;
          if ($content_type) {
            // How many more items we need to retrieve.
            $items_no = $items_to_display - $items_count;

            $query = \Drupal::entityQuery('node')
              ->condition('type', $content_type)
              ->condition('status', 1, '=')
              ->range(0, $items_no);

            $entity_type_manager = \Drupal::entityTypeManager();

            // Filter by Solution term if selected.
            $solution = $block_content->get('field_solutions')->target_id;
            if ($solution) {
              $query->condition('field_solutions.entity.tid', $solution);
            }

            // Filter by term if selected.
            $categories = $block_content->get('field_content_category')->getValue();
            if (!empty($categories)) {
              $term_ids = [];
              foreach ($categories as $category) {
                $term_ids[] = $category['target_id'];
              }

              // Get vocabulary from first term.
              $term = $entity_type_manager->getStorage('taxonomy_term')->load(reset($term_ids));
              $vid = $term->bundle();

              $category_fields = [
                'solutions' => 'field_solutions',
                'degree_category' => 'field_degree_category',
                'insight_types' => 'field_insight_type',
                'coe_locations' => 'field_coe_location',
                'class_locations' => 'field_class_location',
                'event_categories' => 'field_event_categories',
                'topics' => 'field_topics',
                'certificate_type' => 'field_certificate_type',
                'centers' => 'field_centers',
                'knowledge_network_tags' => 'field_kn_tags',
                'life_long_learning_categories'=> 'field_ll_categories',
              ];

              // If a category has been chosen and there is a corresponding
              // taxonomy field, filter by term.
              if (isset($category_fields[$vid])) {
                $query->condition($category_fields[$vid], $term_ids, 'IN');
              }
            }

            // Filter by related content if selected.
            $ref_fields = ['field_regions', 'field_degrees', 'field_certificates', 'field_author'];
            foreach ($ref_fields as $ref_field) {
              if ($block_content->hasField($ref_field)) {
                $refs = $block_content->get($ref_field)->getValue();
                if (!empty($refs)) {
                  $ref_ids = [];
                  foreach ($refs as $ref) {
                    $ref_ids[] = $ref['target_id'];
                  }

                  $query->condition($ref_field, $ref_ids, 'IN');
                }
              }
            }

            // Apply chosen sort order.
            $sort_order = FALSE;
            if ($block_content->hasField('field_sort_order')) {
              $sort_order = $block_content->get('field_sort_order')->value;
            }
            if ($sort_order) {

              switch ($sort_order) {
                case 'date':
                  $query->sort('created', 'DESC');
                  break;

                case 'title':
                  $query->sort('title', 'ASC');
                  break;

                case 'event_date':
                  $date = new DrupalDateTime('now');
                  $date->setTimezone(new \DateTimezone(DateTimeItemInterface::STORAGE_TIMEZONE));
                  $formatted = $date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
                  // Make sure event isn't in the past, and sort by soonest first.
                  $query->condition('field_end_date', $formatted, '>=');
                  $query->sort('field_event_date', 'ASC');
                  break;
              }
            }

            if (!empty($selected_nids)) {
              $query->condition('nid', $selected_nids, 'NOT IN');
            }
            
            $results = $query->execute();

            if ($results) {
              // Load each node and build a render array of its Card view mode.
              foreach ($results as $nid) {
                $view_builder = $entity_type_manager->getViewBuilder('node');
                $storage = $entity_type_manager->getStorage('node');
                $node = $storage->load($nid);
                $build = $view_builder->view($node, 'card');
                $new_items[] = $build;
              }
            }
          }
        }

        $variables['items'] = $new_items;
      }

      break;

    case 'insights_grid':
        if ($block_content) {
          $storage = \Drupal::entityTypeManager()->getStorage('node');
          $path_alias = \Drupal::service('path_alias.manager');
          $media_images = \Drupal::entityQuery('media')->condition('bundle', 'image')->condition('directory', '818')->execute();

          $items_to_display = 0;
          if ($block_content->hasField('field_items_to_display')) {
            $items_to_display = $block_content->get('field_items_to_display')->value;
          }

          $sort_by = FALSE;
          $sort_order = FALSE;
          if ($block_content->hasField('field_sort_order')) {
            $sort_order = $block_content->get('field_sort_order')->value;
          }
          if ($sort_order == 'date') {
            $sort_by = 'created';
            $sort_order = 'desc';
          } elseif ($sort_order == 'title') {
            $sort_by = 'title';
            $sort_order = 'asc';
          } else {
            $sort_by = FALSE;
            $sort_order = FALSE;
          }

          // Replace with filtering by different taxonomy.
          // $tags = $block_content->get('field_tags')->getValue();
      
          // First cell configurations
          $title = $block_content->get('field_title')->value;
          if ($cta = $block_content->get('field_link')->getValue()) {
            $cta = $block_content->get('field_link')->view();
          }
          $bg_color = $block_content->get('field_background_color')->value;
          if (!$bg_color || $bg_color == 'default') {
            $bg_color = 'bg-blue-dark';
          }

          $new_items = $array_items = $grid_items = $results = [];
          if ($insight_items = $block_content->get('field_items')->getValue()) {
            $new_items = array_column($insight_items, 'target_id');
          }
      
          $items_count = count($new_items);
          // If we have fewer items than needed.
          if ($items_count < $items_to_display) {
            $items_no = $items_to_display - $items_count;
            $query = \Drupal::entityQuery('node')
              ->condition('type', 'insight')
              ->condition('status', 1, '=')
              ->range(0, $items_no);

            // Sorting
            if ($sort_by && $sort_order) {
              $query = $query->sort($sort_by, $sort_order);
            }

            // Don't fetch nodes that has already added in the Items field
            if ($new_items) {
              $query = $query->condition('nid', $new_items, 'NOT IN');
            }
            // Search nodes if tags are selected
            // if(count($tags)) $query->condition('field_tags', array_column($tags, 'target_id'), 'IN');
      
            $results = $query->execute();
          }
          
          $results = ($new_items) ? array_merge($results, $new_items) : $results;

          // TODO - Need to decide followings:
          /**
           * Should we maintain the sorting order for those Nodes that are selected in Items field?
           * If yes then we keep the below code block otherwise remove it
           */
          if ($new_items) {
            $query = \Drupal::entityQuery('node')
              ->condition('type', 'insight')
              ->condition('status', 1, '=')
              ->condition('nid', $results, 'IN');

            // Sorting
            if ($sort_by && $sort_order) {
              $query = $query->sort($sort_by, $sort_order);
            }
            
            $results = $query->execute();
          }

          if ($results) {
            $results = array_values($results);

            $bottom_image = 1;
            foreach ($results as $key => $nid) {
              $node = $storage->load($nid);
              
              if ($insight_image = $node->get('field_image')->referencedEntities()) {
                $insight_image = reset($insight_image);
                $insight_image = $insight_image->get('field_media_image')->getValue();
                $insight_image = reset($insight_image)['target_id'];
              }
              elseif ($article_image = $node->get('field_article_image')->getValue()) {
                $insight_image = reset($article_image)['target_id'];
              }
              elseif ($random_image = array_rand($media_images, 1)) { // Get a random image from the library
                $insight_image = Media::load($media_images[$random_image]);
                $insight_image = $insight_image->get('field_media_image')->getValue();
                $insight_image = reset($insight_image)['target_id'];
                unset($media_images[$random_image]); // Unset not to be duplicated
              }

              if ($insight_image) {
                $insight_image = File::load($insight_image)->getFileUri();
                $insight_image = ImageStyle::load('large')->buildUrl($insight_image);
              }
              
              $array_items[$key] = [
                'insight_image' => $insight_image,
              ];
            }

            $entity_type_manager = \Drupal::entityTypeManager();

            foreach ($results as $key => $nid) {
              $view_builder = $entity_type_manager->getViewBuilder('node');
              $storage = $entity_type_manager->getStorage('node');
              $node = $storage->load($nid);
              $build = $view_builder->view($node, 'grid');

              if ($key < 1 && $title) {
                $build['insight_first'] = [
                  'title' =>  $title,
                  'cta' => $cta,
                  'bg_color' => $bg_color
                ];
              }

              $grid_items[] = $build;
            }
          }

          $variables['items'] = $grid_items;
        }
        
        break;

    case 'blockquote':
      $variables['blockquote_type'] = $variables['content']['field_quote_layout'][0]["#markup"];

      break;

    case 'hero':
    case 'card_and_image':
    case 'apply_now':
      $image_field = '';
      $value = NULL;

      $img_fields = ['field_media'];
      if ($block_content) {
        foreach ($img_fields as $img_field) {
          if (empty($value) && $block_content->hasField($img_field)) {
            $value = $block_content->get($img_field)->target_id;
            if ($value) {
              $image_field = $img_field;
            }
          }
        }
      }

      if (!empty($value) && $image_field) {
        $image_style = 'hero';

        if ($block_type == 'hero' && $block_content->hasField('field_hero_size')) {
          if ($block_content->get('field_hero_size')->value == 'lg') {
            $image_style = 'hero_big';
          }
        }

        $uuid = $variables['configuration']['uuid'] ?? '';
        $css_class = 'block-' . $uuid;
        $variables['attributes']['class'][] = $css_class;
        $css_selector = '.' . $css_class;

        $style_tag = ResponsiveBackgroundImage::generateMediaQueries($css_selector, $block_content, $image_field, $image_style);

        if ($style_tag) {
          $variables['#attached']['html_head'][] = $style_tag;
        }
      }

      if ($block_type == 'apply_now') {
        $node = NULL;

        if ($nid = $block_content->get('field_degree')->target_id) {
          $entity_type_manager = \Drupal::entityTypeManager();
          $storage = $entity_type_manager->getStorage('node');
          $node = $storage->load($nid);
        }

        // If a degree was not selected, get the current degree node.
        if (!$node) {
          $node = \Drupal::routeMatch()->getParameter('node');
        }

        if ($node instanceof NodeInterface  && $node->bundle() == 'degree') {
          $variables['apply_now_form'] = Drupal::formBuilder()
          ->getForm('Drupal\tb_degrees\Form\ApplyNowForm', $node);
        }
      }

      if ($block_type == 'hero') {
        $variables['language_block'] = '';

        // Get current url.
        $current_path = \Drupal::service('path.current')->getPath();
        $alias = \Drupal::service('path_alias.manager')->getAliasByPath($current_path);

        // If on one of the 100ML pages, add the language block.
        if ($alias && strpos($alias, '/lifelong-learning/100-million-learners') === 0) {
          $block = \Drupal\block\Entity\Block::load('dropdownlanguagecontent');
          if ($block) {
            $variables['language_block'] = \Drupal::entityTypeManager()
              ->getViewBuilder('block')
              ->view($block);
          }
        }
      }

      break;

    case 'dropdown_language':
      if (!empty($variables['label'])) {
        // Get the correct language for the fieldblocks.
        // Taken from https://www.drupal.org/docs/drupal-apis/configuration-api/configuration-override-system#s-language-overrides
        // Doesn't seem to work because block titles are INTERFACE, not CONTENT. Which is questionable.
        // Our interface is always English.
        $language_manager = \Drupal::languageManager();
        // Load the content language for the page.
        $langcode = $language_manager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $language = $language_manager->getLanguage($langcode);

        // Keep the original language to reset back later.
        $originalLanguage = $language_manager->getConfigOverrideLanguage();
        // Set the translation target language on the configuration factory.
        $language_manager->setConfigOverrideLanguage($language);
        $config = \Drupal::config('block.block.' . $variables['id']);
        $settings = $config->get('settings');

        // Set the translated language.
        $variables['label'] = $settings['label'];
        // Set the configuration language back.
        $language_manager->setConfigOverrideLanguage($originalLanguage);
      }

      break;

    case 'image_gallery':
      $gallery_imgs = $thumbnails = $captions = [];

      if ($block_content->hasField('field_gallery_slide')) {
        $paras = $block_content->field_gallery_slide->referencedEntities();
        $builder = \Drupal::entityTypeManager()->getViewBuilder('paragraph');

        foreach ($paras as $para) {
          // $id = $para->getRevisionId();

          $gallery_imgs[] = $builder->view($para, 'image_gallery');
          $thumbnails[] = $builder->view($para, 'gallery_thumbnail');
          $captions[] = $builder->view($para, 'gallery_caption');
        }
      }

      $variables['gallery_imgs'] = $gallery_imgs;
      $variables['thumbnails'] = $thumbnails;
      $variables['captions'] = $captions;

      break;

    case 'sitemap':
      $array = _create_sitemap_array();
      $variables['sitemap_tree'] = _print_array_list($array);

      break;
  }
}

// XML TO HTML SITEMAP BEGINS.
function _create_sitemap_array(){
  $base_url = \Drupal::request()->getSchemeAndHttpHost();
  $sitemap_URL = $base_url . "/sitemap.xml";
  // json encode/decode to avoid using an object.
  $urls = json_decode(json_encode(simplexml_load_file($sitemap_URL) ), TRUE);
  // Empty array for resulting tree.
  $array = [];
  // Data comes in under 'url' key, don't know why.
  foreach ($urls["url"] as $url){
      // Parse the path.
      $url_string = parse_url($url["loc"], PHP_URL_PATH);
      // Explode the path (avoiding the leading slash).
      $parts = explode("/", substr($url_string, 1));
      // Sort by index.
      krsort($parts);
      // Set vars.
      $line_array = null;
      // For each part of the url.
      foreach ($parts as $key => $value){
          // If it's a leaf, set the line_array to url.
          if ($line_array == null)
          {
              $line_array = [$key => $url_string];
          }
          // Else create branch and set key to value and point to new array.
          else
          {
              $temp_array = $line_array;
              $line_array = [$value => $temp_array];
          }
      }
      // Recursively merge array after each url.
      $array = array_merge_recursive($array, $line_array);
  }
  return $array;
}

function _print_array_list($array, $output = '') {
  $output .= "<ul>";
  foreach($array as $key => $value) {
      if (is_array($value)) {
          $output .= ("<li><span class='heading'>" . $key . "</span></li>");
          $output = _print_array_list($value, $output);
          continue;
      }
      $output .= ("<li><a href=". $value . ">" . $value . "</a></li>");
  }
  $output .= "</ul>";
  return $output;
}
// XML TO HTML SITEMAP ENDS.

/**
 * Implements hook_preprocess_TEMPLATE().
 * Preprocess field--field_block
 * https://www.drupal.org/project/drupal/issues/2704331#comment-13389866
 * Make entity reference fields use twig template
 */
function thunderbird_preprocess_field__field_block__default(&$variables) {
    /** @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    $entity = $variables['element']['#object'];

    // Loop over our blocks and pass UUIDs into templates to be used by Twig Tweak.
    foreach ($variables['items'] as &$item) {
        $item['block_uuid'] = $item['content']['#block_content']->uuid();
        $vars['block_title'] = $item['content']['#block_content']->label();
    }
}

/**
* Implements hook_preprocess_HOOK() for content blocks.
*/
function thunderbird_preprocess_asu_footer__footer_block(&$variables) {
  $variables['wechat_url'] = 'http://weixin.qq.com/r/iR2dhZLELc9Srdbm90ib ';
}


/**
 * Implements hook_preprocess_page().
 */
function thunderbird_preprocess_page(array &$variables) {
  if (\Drupal::service('path.matcher')->isFrontPage()) {
    $variables['#attached']['library'][] = 'thunderbird/autoplay-mobile-video';
  }
}
