<?php
/**
 * @file
 * Theme and preprocess functions for fields.
 */

use Drupal\Core\Url;

/**
 * Implements hook_preprocess_HOOK().
 */
function thunderbird_preprocess_field(&$variables) {
  $field_name = $variables['element']['#field_name'];
  $container = $variables['element']['#object'];
  $view_mode = $variables['element']['#view_mode'];
  $block_type = $container->bundle();

  // Field KN Tags on insight.
  if ($field_name == 'field_kn_tags' && !in_array($view_mode, ['card', 'grid']) ) {
    $topics = [];
    $topic_entities = $container->get('field_kn_tags')->referencedEntities();

    foreach ($topic_entities as $key => $entity) {
      $name = $entity->label();
      $id = $entity->id();
      $topics[$name] = $id;
    }

    foreach ($variables['items'] as $key => $value) {
      $label = $value['content']['#plain_text'] ?? '';

      if (isset($topics[$label])) {
        // Wrap the topic in a link to the Insights listing page.
        $url_object = Url::fromUri('internal:/thought-leadership/insights', [
          'query' => [
            'kn_tags' => $topics[$label],
          ]
        ]);

        $link = [
          '#type' => 'link',
          '#url' => $url_object,
          '#title' => $label,
        ];
        $variables['items'][$key]['content'] = $link;
      }
    }
  }
}
