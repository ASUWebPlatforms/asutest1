<?php

namespace Drupal\tb_webform\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Returns responses for Thunderbird Webform routes.
 */
class TbWebformController extends ControllerBase {

  /**
   * Builds the response.
   */
  public function build() {

    $build['content'] = [
      '#type' => 'item',
      '#markup' => $this->t('It works!'),
    ];

    return $build;
  }

}
