<?php

namespace Drupal\tb_gdmi_reports;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a GDMI report entity type.
 */
interface GdmiReportInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
