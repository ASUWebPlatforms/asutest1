<?php

namespace Drupal\Tests\instapage\Functional;

use Drupal\Core\Config\Config;
use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Contains tests for testing Instapage form access.
 *
 * @group instapage
 */
class PageFormAccessTest extends BrowserTestBase {

  /**
   * Settings configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $settingsConfig;

  /**
   * Pages configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'instapage',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public function setUp(): void {
    parent::setUp();
    $this->settingsConfig = $this->config('instapage.settings');
    $this->pagesConfig = $this->config('instapage.pages');
  }

  /**
   * Tests the Instapage module forms.
   */
  public function testPageFormAccess(): void {
    $this->pagesConfig->set('page_labels', []);
    $this->pagesConfig->set('instapage_pages', []);
    $this->pagesConfig->save();
    $this->settingsConfig->set('instapage_user_token', 'iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3')->save();

    // Test access to forms with user that doesn't have required permissions.
    $this->assertPageFormStatusCode(403, [], 'instapage.page_new');
    $this->assertPageFormStatusCode(403, [], 'instapage.page_edit', 100);
    $this->assertPageFormStatusCode(403, [], 'instapage.page_delete', 100);
    $this->assertPageFormStatusCode(403, [], 'instapage.settings');

    // Test access to Instapage settings form.
    $this->assertPageFormStatusCode(200, ['administer instapage settings'], 'instapage.settings');
    // Test access to new page form.
    $this->settingsConfig->set('instapage_user_id', 'testing@testing.com')->save();
    $this->pagesConfig->set('page_labels', [123456 => 'Testing page 0'])->save();
    $this->assertPageFormStatusCode(200, ['administer instapage landing pages'], 'instapage.page_new');
    // Tests logged in user form access with connected Instapage account.
    $this->assertNewPageFormUserAccess();
    // Test access to page edit form.
    // If page doesn't exist.
    $this->assertPageFormStatusCode(403, ['administer instapage landing pages'], 'instapage.page_edit', 123);
    // If page exists.
    $this->pagesConfig->set('page_labels', [123 => 'Testing page 1']);
    $this->pagesConfig->set('instapage_pages', [123 => 'testing-path-1'])->save();
    $this->assertPageFormStatusCode(200, ['administer instapage landing pages'], 'instapage.page_edit', 123);
    // Test access to page delete form.
    // If page doesn't exist.
    $this->pagesConfig->clear('instapage_pages')->save();
    $this->assertPageFormStatusCode(403, ['administer instapage landing pages'], 'instapage.page_delete', 123);
    // If page exists.
    $this->pagesConfig->set('instapage_pages', [123 => 'testing-path-1'])->save();
    $this->assertPageFormStatusCode(200, ['administer instapage landing pages'], 'instapage.page_delete', 123);
  }

  /**
   * User login function.
   *
   * @param array $permissions
   *   User permissions.
   */
  private function loginCreateUser(array $permissions): void {
    $this->drupalLogin($this->drupalCreateUser($permissions));
  }

  /**
   * Function tests access to Instapage forms.
   *
   * @param int $expectedStatusCode
   *   Expected return code.
   * @param array $permissions
   *   User permissions.
   * @param string $routeName
   *   Route name.
   * @param string|null $routeParameter
   *   Route parameter value. Defaults to NULL.
   */
  private function assertPageFormStatusCode(int $expectedStatusCode, array $permissions, string $routeName, string $routeParameter = NULL): void {
    $this->loginCreateUser($permissions);
    if (isset($routeParameter)) {
      $this->drupalGet(Url::fromRoute($routeName, ['instapage_id' => $routeParameter]));
    }
    else {
      $this->drupalGet(Url::fromRoute($routeName));
    }
    $this->assertSession()->statusCodeEquals($expectedStatusCode);
  }

  /**
   * Visits new page form as logged in user.
   */
  private function assertNewPageFormUserAccess(): void {
    $this->loginCreateUser(['administer instapage landing pages']);
    if (!empty($this->settingsConfig->get('instapage_user_token'))) {
      $this->drupalGet(Url::fromRoute('instapage.page_new'));
      $this->assertSession()->fieldExists('page');
      $this->assertSession()->fieldExists('path');
      $this->assertSession()->fieldExists('subaccount');
    }
    else {
      $this->drupalGet(Url::fromRoute('instapage.page_new'));
      $this->assertSession()->fieldNotExists('page');
      $this->assertSession()->fieldNotExists('path');
      $this->assertSession()->fieldNotExists('subaccount');
    }
  }

}
