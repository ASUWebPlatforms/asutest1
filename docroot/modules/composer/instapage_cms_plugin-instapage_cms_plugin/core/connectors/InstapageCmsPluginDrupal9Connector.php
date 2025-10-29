<?php

use Drupal\Core\Database\Database as Database;
use Drupal\Core\Database\StatementInterface;

/**
 * Class that utilizes native Drupal 9 functions to perform actions like remote requests and DB operations.
 */
class InstapageCmsPluginDrupal9Connector extends InstapageCmsPluginDrupal8Connector {
  /**
   * Prepares the basic query with proper metadata/tags and base fields.
   *
   * @param string $sql SQL query. %s can be used to output pre-formatted values.
   *
   * @return StatementInterface SQL query ready to execute in Drupal 8.
   */
  public function prepare($sql) {
    $sql = str_replace(array('\'%s\'', '%s'), '?', $sql);
    $connection = Database::getConnection();

    return $connection->prepareStatement($sql, []);
  }
}
