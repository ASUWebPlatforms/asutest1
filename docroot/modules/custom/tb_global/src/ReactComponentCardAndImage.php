<?php

namespace Drupal\tb_global;

use Drupal\asu_react_core\ReactComponentCardAndImage as OriginalCardAndImage;

class ReactComponentCardAndImage extends OriginalCardAndImage {

  public function buildSettings(&$variables) {
    parent::buildSettings($variables);

    $keys = array_keys($variables['content']['#attached']['library'], 'asu_react_core/card');

    // Unset ASU React component card library.
    if (is_array($keys)) {
      foreach ($keys as $key) {
        unset($variables['content']['#attached']['library'][$key]);
      }
    }
  }
}
