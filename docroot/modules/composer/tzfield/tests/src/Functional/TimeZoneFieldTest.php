<?php

namespace Drupal\Tests\tzfield\Functional;

use Behat\Mink\Exception\ElementNotFoundException;
use Drupal\Core\Validation\Plugin\Validation\Constraint\AllowedValuesConstraint;
use Drupal\Tests\BrowserTestBase;
use Drupal\node\Entity\Node;
use Drupal\user\UserInterface;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * Tests for time zone field module.
 *
 * @group tzfield
 */
class TimeZoneFieldTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['field_ui', 'node', 'tzfield'];

  /**
   * Functional tests for tzfield.
   */
  public function testTimeZoneField(): void {
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $adminUser = $this->drupalCreateUser([
      'administer node display',
      'administer node fields',
      'administer node form display',
      'create article content',
    ]);
    $this->assertNotEmpty($adminUser);
    $this->drupalLogin($adminUser);
    $this->drupalGet('admin/structure/types/manage/article/fields/add-field');
    try {
      // Drupal 10.1 and earlier.
      $this->submitForm([
        'new_storage_type' => 'tzfield',
        'label' => 'Time zone',
        'field_name' => 'time_zone',
      ], 'Save and continue');
    }
    catch (ElementNotFoundException $e) {
      try {
        // Drupal 10.2.
        $this->submitForm([
          'new_storage_type' => 'tzfield',
          'label' => 'Time zone',
          'field_name' => 'time_zone',
        ], 'Continue');
        $this->submitForm([], 'Save settings');
      }
      catch (ElementNotFoundException $e) {
        // Drupal 10.3 and later.
        try {
          $this->submitForm(['new_storage_type' => 'tzfield'], 'Continue');
        }
        catch (ElementNotFoundException $e) {
          // Drupal 11.2 and later.
          $this->drupalGet('admin/structure/types/manage/article/fields/add-field/tzfield/false');
        }
        $this->submitForm(['label' => 'Time zone', 'field_name' => 'time_zone'], 'Continue');
        try {
          $this->submitForm([], 'Save settings');
        }
        catch (ElementNotFoundException $e) {
          // Drupal 11.2 and later.
          $this->submitForm([], 'Save');
        }
      }
    }
    $this->drupalGet('admin/structure/types/manage/article/form-display');
    $this->submitForm(['fields[field_time_zone][type]' => 'tzfield_offset'], 'Save');
    $this->drupalGet('node/add/article');
    $option = $this->assertSession()->selectExists('edit-field-time-zone-0-value')->find('css', 'option[value=UTC]');
    $this->assertNotNull($option);
    $this->assertSame('(UTC+00:00) UTC', $option->getText());
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_time_zone');
    $this->submitForm(['settings[exclude][]' => ['UTC']], 'Save settings');
    $this->drupalGet('node/add/article');
    $option = $this->assertSession()->selectExists('edit-field-time-zone-0-value')->find('css', 'option[value=UTC]');
    $this->assertNull($option);
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->submitForm(['fields[field_time_zone][type]' => 'tzfield_date'], 'Save');
    $this->drupalGet('node/add/article');
    $this->submitForm([
      'title[0][value]' => 'My title',
      'field_time_zone[0][value]' => 'America/Cayman',
    ], 'Save');
    $this->assertSession()->pageTextContains('Time zone EST');
    $this->drupalGet('admin/structure/types/manage/article/display');
    $this->submitForm([], 'field_time_zone_settings_edit');
    $this->submitForm(['fields[field_time_zone][settings_edit_form][settings][format]' => 'e T'], 'Save');
    $this->drupalGet('node/1');
    $this->assertSession()->pageTextContains('Time zone America/Cayman EST');
    // Test default_user field setting.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_time_zone');
    $this->submitForm(['settings[default_user]' => TRUE], 'Save settings');
    $this->drupalGet('node/add/article');
    $this->submitForm(['title[0][value]' => 'My title'], 'Preview');
    $this->assertSession()->pageTextContains('Time zone Australia/Sydney AE');
    // Test default_site field setting.
    $this->drupalGet('admin/structure/types/manage/article/fields/node.article.field_time_zone');
    $this->submitForm(['settings[default_site]' => TRUE], 'Save settings');
    $this->config('system.date')->set('timezone.user.default', UserInterface::TIMEZONE_EMPTY)->save();
    $adminUser->set('timezone', [])->save();
    $this->drupalGet('node/add/article');
    $this->submitForm(['title[0][value]' => 'My title'], 'Preview');
    $this->assertSession()->pageTextContains('Time zone Australia/Sydney AE');
    // Test constraint validator.
    $this->rebuildContainer();
    $node = Node::create(['type' => 'article', 'title' => 'My title', 'field_time_zone' => 'UTC']);
    $constraints = $node->validate();
    $this->assertCount(1, $constraints);
    $this->assertInstanceOf(ConstraintViolation::class, $constraints[0]);
    $this->assertInstanceOf(AllowedValuesConstraint::class, $constraints[0]->getConstraint());
    $node->field_time_zone->value = 'America/New_York';
    $this->assertCount(0, $node->validate());
  }

}
