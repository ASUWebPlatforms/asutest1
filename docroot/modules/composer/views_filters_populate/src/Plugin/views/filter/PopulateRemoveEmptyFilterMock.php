<?php

namespace Drupal\views_filters_populate\Plugin\views\filter;

use Drupal\views\Plugin\views\HandlerBase;

/**
 * Filter mock class that takes care of removing populated filters
 * from the view if the populated value is empty and exposed.
 */
class PopulateRemoveEmptyFilterMock extends HandlerBase {
  private $views_filters_populate_handler_caller;

  public function __construct($handler) {
    $this->views_filters_populate_handler_caller = $handler;
  }

  public function preQuery() {
    $handler = $this->views_filters_populate_handler_caller;
    foreach ($handler->options['filters'] as $id) {
      unset($handler->view->filter[$id]);
    }
    foreach ($handler->view->filter as $k => $filter) {
      if ($filter === $this) {
        unset($handler->view->filter[$k]);
      }
    }
  }
}
