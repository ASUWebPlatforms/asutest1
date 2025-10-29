<?php

namespace Drupal\scheduled_publish\Commands;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\scheduled_publish\Service\ScheduledPublishCron;
use Drush\Commands\DrushCommands;

// cspell:ignore schp

/**
 * Class ScheduledPublishCommands.
 *
 * Provide Drush command for publishing scheduled content.
 *
 * @package Drupal\scheduled_publish\Commands
 */
class ScheduledPublishCommands extends DrushCommands {

  use StringTranslationTrait;

  /**
   * The cron service.
   *
   * @var \Drupal\scheduled_publish\Service\ScheduledPublishCron
   */
  private $publishCron;

  /**
   * ScheduledPublishCommands constructor.
   *
   * @param \Drupal\scheduled_publish\Service\ScheduledPublishCron $publishCron
   *   THe cron service.
   */
  public function __construct(ScheduledPublishCron $publishCron) {
    parent::__construct();
    $this->publishCron = $publishCron;
  }

  /**
   * Update the schedule when Drush command run.
   *
   * @command scheduled_publish:doUpdate
   * @aliases schp
   */
  public function doUpdate() {
    $this->publishCron->doUpdate();
    $this->logger()->notice($this->t('Scheduled publish updates done.'));
  }

}
