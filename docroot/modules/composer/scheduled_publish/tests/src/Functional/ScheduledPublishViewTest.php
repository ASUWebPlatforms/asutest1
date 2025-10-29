<?php

declare(strict_types=1);

namespace Drupal\Tests\scheduled_publish\Functional;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\content_moderation\Traits\ContentModerationTestTrait;
use Drupal\user\Entity\User;
use Drupal\workflows\Entity\Workflow;

/**
 * Test description.
 *
 * @group scheduled_publish
 */
final class ScheduledPublishViewTest extends BrowserTestBase {

  use ContentModerationTestTrait;

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'node',
    'field',
    'field_ui',
    'views',
    'scheduled_publish',
    'content_moderation',
  ];

  /**
   * The admin user.
   *
   * @var \Drupal\user\Entity\User
   */
  protected User $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser();
    $this->adminUser->addRole($this->createAdminRole('admin', 'admin'));
    $this->adminUser->save();
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Test page displays message when no scheduled publish field added.
   */
  public function testViewWhenNoScheduledPublishField(): void {
    $this->drupalGet('admin/content/scheduled-publish');
    $this->assertSession()->elementExists('xpath', '//h1[text() = "Scheduled publish"]');
    $this->assertSession()->pageTextContains('A scheduled publish field has to be added to a content type before this functionality can be used');
  }

  /**
   * Test scheduled publish view header displayed.
   */
  public function testViewHeader(): void {
    // Create article content type.
    $this->createContentType(['type' => 'article', 'name' => 'Article']);

    // Add scheduled publish field.
    FieldStorageConfig::create([
      'field_name' => 'field_publish',
      'type' => 'scheduled_publish',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_publish',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Publish',
    ])->save();

    // Test that bulk scheduling link displayed.
    $this->drupalGet('admin/content/scheduled-publish');
    $this->assertSession()->elementExists('xpath', '//a[text() = "Add bulk scheduled publishing entries"]');
  }

  /**
   * Test scheduled publish does not break unmoderated content.
   */
  public function testUnmoderatedContentFallback(): void {
    $this->createContentType(['type' => 'article', 'name' => 'Article']);
    $this->createContentType(['type' => 'page', 'name' => 'Page']);
    $this->createEditorialWorkflow();

    // Add scheduled publish field.
    FieldStorageConfig::create([
      'field_name' => 'field_publish',
      'type' => 'scheduled_publish',
      'entity_type' => 'node',
      'cardinality' => 1,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_publish',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Publish',
    ])->save();
    $defaultFormDisplay = EntityFormDisplay::load('node.article.default');
    $defaultFormDisplay->setComponent('field_publish', [
      'type' => 'scheduled_publish',
    ])->save();

    $editorialWorkflow = Workflow::load('editorial');
    $typeSettings = $editorialWorkflow->get('type_settings');
    $typeSettings['entity_types'] = ['node' => ['page']];
    $editorialWorkflow->set('type_settings', $typeSettings)->save();

    // Test that bulk scheduling link displayed.
    $this->drupalGet('node/add/article');
    $this->assertSession()->pageTextContains('Scheduled moderation is not available');
  }

}
