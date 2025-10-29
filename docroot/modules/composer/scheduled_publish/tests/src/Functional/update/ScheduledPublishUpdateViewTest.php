<?php

namespace Drupal\Tests\scheduled_publish\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests update functions for the Block Content module.
 *
 * @group scheduled_publish_update
 */
class ScheduledPublishUpdateViewTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles(): void {
    // Database script for Drupal 10.3 or higher.
    if (version_compare(\Drupal::VERSION, '10.3.2', '>')) {
      $this->databaseDumpFiles = [
        __DIR__ . '/../../../fixtures/update/drupal-10.3.2-scheduled_publish-3.10.php.gz',
      ];
      return;
    }

    $this->databaseDumpFiles = [
      __DIR__ . '/../../../fixtures/update/drupal-10.1.2-scheduled_publish-3.10.php.gz',
    ];
  }

  /**
   * Tests update hook removes header link.
   *
   * @test
   */
  public function testUpdateViewHeaderRemoved(): void {
    if (version_compare(\Drupal::VERSION, '10.0', '<')) {
      $this->markTestSkipped();
      return;
    }

    $adminUser = $this->drupalCreateUser();
    $adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $adminUser->save();
    $this->drupalLogin($adminUser);

    // Confirm view definition contains global text area.
    $this->drupalGet('admin/structure/views/view/scheduled_publish');
    $this->assertSession()->responseContains('Global: Text area (Global: Text area)');

    $this->runUpdates();

    // Confirm view definition contains global text area.
    $this->drupalGet('admin/structure/views/view/scheduled_publish');
    $this->assertSession()->responseNotContains('Global: Text area (Global: Text area)');
  }

}
