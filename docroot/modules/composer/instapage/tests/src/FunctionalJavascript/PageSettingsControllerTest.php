<?php

namespace Drupal\Tests\instapage\FunctionalJavascript;

use Drupal\Core\Config\Config;
use Drupal\Core\Url;
use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the page settings controller.
 *
 * @group instapage
 *
 * @package Drupal\Tests\instapage\FunctionalJavascript
 */
class PageSettingsControllerTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'instapage',
    'instapage_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The test user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * Settings configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $settingsConfig;

  /**
   * Testing settings configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $testingConfig;

  /**
   * Testing token variable.
   *
   * @var string
   */
  protected string $token;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->user = $this->drupalCreateUser([
      'administer instapage settings',
      'administer instapage landing pages',
      'access instapage landing pages',
    ]);
    $this->drupalLogin($this->user);
    $this->settingsConfig = $this->config('instapage.settings');
    $this->testingConfig = $this->config('instapage_test.testing');
    $this->token = 'iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3';
  }

  /**
   * Tests the modules landing page.
   */
  public function testPageSettingsController(): void {
    $session = $this->getSession();
    $webAssert = $this->assertSession();

    // Visits the page as logged in user without connected Instapage account.
    $this->drupalGet(Url::fromRoute('instapage.landing_pages'));
    $webAssert->pageTextContains("You don't have the Instapage account setup yet.");
    $webAssert->pageTextContains("Please connect your account here.");

    // Re-visits the page with connected Instapage account.
    // Account doesn't have any site published for Drupal.
    $this->settingsConfig->set('instapage_user_id', 'testing@testing.com');
    $this->settingsConfig->set('instapage_user_token', $this->token)->save();
    $this->testingConfig->set('sub_accounts', TRUE);
    $this->testingConfig->set('acc_keys', TRUE);
    $this->testingConfig->save();
    $this->drupalGet(Url::fromRoute('instapage.landing_pages'));
    $webAssert->pageTextContains("Please add a page on the Instapage app before continuing.");

    // Adds the pages to Instapage account and re-visits the site.
    $this->testingConfig->set('page_list', TRUE)->save();
    $this->drupalGet(Url::fromRoute('instapage.landing_pages'));
    $webAssert->pageTextContains("Below is a list of Instapage pages connected to your website. Click 'Add new page' to add another one.");

    // Clicks 'Add new page' button and opens PageNewForm in dialog on page.
    $page = $session->getPage();
    $webAssert->linkExists('Add new page');
    $page->clickLink('Add new page');
    $this->assertNotEmpty($webAssert->waitForElementVisible('css', '.ui-dialog'));
    $webAssert->pageTextContains("Without leading forward slash");
    $webAssert->fieldExists('subaccount');
    $webAssert->fieldExists('page');
    $webAssert->fieldExists('path');
    $webAssert->buttonExists('Save');

    // Closes the dialog.
    $webAssert->buttonExists('Cancel')->click();
    $this->assertNotEmpty($webAssert->waitForElementRemoved('css', '.ui-dialog'));

    // Opens dialog again and adds new page through form.
    $page->clickLink('Add new page');
    $this->assertNotEmpty($webAssert->waitForElementVisible('css', '.ui-dialog'));
    $webAssert->fieldExists('page');
    $page->findField('page')->press();
    $page->selectFieldOption('page', 'Testing Page 2 (Personal Projects)');
    $page->findField('path')->setValue('testing-path-2');
    $webAssert->buttonExists('Save')->click();
    $this->assertNotEmpty($webAssert->waitForElementRemoved('css', '.ui-dialog'));
    $webAssert->pageTextContains("Path for Testing Page 2 (Personal Projects) has been saved.");

    // Opens Edit dialog and changes path field, then saves.
    $this->drupalGet(Url::fromRoute('instapage.landing_pages'));
    $webAssert->linkExists('Edit');
    $page->clickLink('Edit');
    $this->assertNotEmpty($webAssert->waitForElementVisible('css', '.ui-dialog'));
    $page->findField('path')->setValue('testing-path-3');
    $webAssert->buttonExists('Save')->click();
    $webAssert->pageTextContains("Path for Testing Page 2 (Personal Projects) has been saved.");

    // Expands Operations tab and opens Delete dialog.
    $this->drupalGet(Url::fromRoute('instapage.landing_pages'));
    $webAssert->elementExists('css', '.dropbutton-arrow')->click();
    $webAssert->linkExists('Delete');
    $page->clickLink('Delete');
    $this->assertNotEmpty($webAssert->waitForElementVisible('css', '.ui-dialog'));
    $webAssert->pageTextContains('Are you sure you want to delete the path and unpublish the page Testing Page 2 (Personal Projects)?');
    $webAssert->buttonExists('Cancel');

    // Deletes page from the application.
    $webAssert->buttonExists('Delete')->click();
    $webAssert->pageTextContains('Path for Testing Page 2 (Personal Projects) has been removed.');

    // Re-visits page with no added page.
    $this->drupalGet(Url::fromRoute('instapage.landing_pages'));
    $webAssert->pageTextContains('There are no items yet.');
  }

}
