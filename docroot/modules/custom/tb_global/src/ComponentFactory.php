<?php


namespace Drupal\tb_global;


class ComponentFactory {

  /**
   * @param $id
   * @param $variables
   * @return ReactComponent
   */
  static public function load($id, $variables) {
    $types = [
      'card_and_image' => '\Drupal\tb_global\ReactComponentCardAndImage'
    ];

    if (!in_array($id, array_keys($types))) {
      return;
    }

    $classname = $types[$id];
    if ($classname) {
      return new $classname($variables);
    }
  }
}
