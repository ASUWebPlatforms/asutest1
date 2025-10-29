<?php

namespace Drupal\scheduled_publish\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\views\Views;

/**
 * Class ScheduledPublishListing.
 *
 * Controller to display the scheduled publish listing.
 *
 * @package Drupal\scheduled_publish\Controller
 */
class ScheduledPublishListing extends ControllerBase {

  /**
   * Gets the listing view if possible.
   */
  public static function viewListing() {
    if (scheduled_publish_get_node_fields()) {
      $page = [];

      $page['header'] = [
        '#prefix' => '<div class="action-links">',
        '#suffix' => '</div>',
      ];
      $url = Url::fromRoute('scheduled_publish.multiple_updates');
      $page['header']['link'] = Link::fromTextAndUrl(new TranslatableMarkup('Add bulk scheduled publishing entries'), $url)->toRenderable();
      $page['header']['link']['#attributes'] = [
        'class' => [
          'button',
          'button-action',
          'button--primary',
          'button--small',
        ],
      ];

      $view = Views::getView('scheduled_publish');
      $view->setDisplay('block_1');
      $page['view'] = $view->buildRenderable();

      return $page;
    }

    return [
      '#type' => 'html_tag',
      '#tag' => 'p',
      '#value' => new TranslatableMarkup('A scheduled publish field has to be added to a content type before this functionality can be used.'),
    ];
  }

}
