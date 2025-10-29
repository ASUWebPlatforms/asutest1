<?php

namespace Drupal\Tests\instapage\Kernel;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormState;
use Drupal\instapage\Form\SettingsForm;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the SettingsForm form.
 *
 * @group instapage
 *
 * @package Drupal\Tests\instapage\Kernel
 */
class SettingsFormTest extends KernelTestBase {

  /**
   * Settings configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $settingsConfig;

  /**
   * Mocked Client service variable.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $mockClient;

  /**
   * Testing email variable.
   *
   * @var string
   */
  protected string $email;

  /**
   * Testing token variable.
   *
   * @var string
   */
  protected string $token;

  /**
   * Testing password variable.
   *
   * @var string
   */
  protected string $password;

  /**
   * Form builder object variable.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'instapage',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['instapage']);

    $this->mockClient = $this->createMock(Client::class);
    $this->container->set('http_client', $this->mockClient);
    $this->token = 'iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3';
    $this->email = 'testing@testing.com';
    $this->password = '123testing123';
    $this->settingsConfig = $this->config('instapage.settings');
    $this->formBuilder = $this->container->get('form_builder');
  }

  /**
   * Tests the buildForm() method as user without connected Instapage account.
   */
  public function testFormBuildNotLoggedIn(): void {
    $form_state = new FormState();
    $form = $this->formBuilder->buildForm(SettingsForm::class, $form_state);
    $this->assertNotNull($form);
    $this->assertCount(0, $form_state->getErrors());
    $this->assertArrayHasKey('instapage_user_email', $form);
    $this->assertArrayHasKey('instapage_user_password', $form);
  }

  /**
   * Tests the buildForm() method as user with connected Instapage account.
   *
   * Method getAccountKeys() call returns successful response.
   */
  public function testFormBuildWithConnectedInstapageAccount(): void {
    $this->settingsConfig->set('instapage_user_id', $this->email);
    $this->settingsConfig->set('instapage_user_token', $this->token)->save();
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
    ]);
    $form_state = new FormState();
    $form = $this->formBuilder->buildForm(SettingsForm::class, $form_state);
    $this->assertNotNull($form);
    $this->assertCount(0, $form_state->getErrors());
    $this->assertArrayNotHasKey('instapage_user_email', $form);
    $this->assertArrayNotHasKey('instapage_user_password', $form);
    $this->assertArrayHasKey('info', $form);
    $this->assertEquals('You are logged in as ' . $this->email . '.<p>Administer your pages <a href="/admin/structure/instapage">here</a>.</p>', $form['info']['#markup']);
  }

  /**
   * Tests the buildForm() method as user with connected Instapage account.
   *
   * Method getAccountKeys() call returns failed response.
   */
  public function testFormBuildWithConnectedFaultyInstapageAccount(): void {
    $this->settingsConfig->set('instapage_user_id', $this->email);
    $this->settingsConfig->set('instapage_user_token', $this->token)->save();
    $this->mockRequestMethod([
      new Response(400, [], '{"success":true,"error":false}', '1.1', 'Bad Request'),
    ]);
    $form_state = new FormState();
    $form = $this->formBuilder->buildForm(SettingsForm::class, $form_state);
    $this->assertNotNull($form);
    $this->assertCount(1, $form_state->getErrors());
    $this->assertEquals('Error from Instapage API: Login failed.', $form_state->getErrors()['form']);
    $this->settingsConfig = $this->config('instapage.settings');
    $this->assertEmpty($this->settingsConfig->get('instapage_user_id'));
    $this->assertEmpty($this->settingsConfig->get('instapage_plugin_hash'));
    $this->assertArrayHasKey('instapage_user_email', $form);
    $this->assertArrayHasKey('instapage_user_password', $form);
  }

  /**
   * Tests the failed validateForm() method call.
   */
  public function testFormValidateFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '{"success":true,"error":false}', '1.1', 'Bad Request'),
    ]);
    $form_state = (new FormState())
      ->setValues([
        'instapage_user_email' => $this->email,
        'instapage_user_password' => $this->password,
      ]);
    $form = $this->formBuilder->buildForm(SettingsForm::class, $form_state);
    $this->formBuilder->validateForm('instapage_admin_settings_form', $form, $form_state);
    $formErrors = $form_state->getErrors();
    $this->assertCount(1, $formErrors);
    $this->assertNotNull($formErrors['form']);
    $this->assertEquals('Error from Instapage API: Login failed.', $formErrors['form']);
  }

  /**
   * Tests the successful validateForm() method call.
   */
  public function testFormValidateSuccess(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"usertoken":"iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3","firstname":"Testing 123","lastname":"UK12345678","email":"testing@testing.com"},"message":"Login succed"}', '1.1', 'OK'),
    ]);
    $form_state = new FormState();
    $form = $this->formBuilder->buildForm(SettingsForm::class, $form_state);
    $form_state
      ->setValues([
        'instapage_user_email' => $this->email,
        'instapage_user_password' => $this->password,
      ]);
    $this->formBuilder->validateForm('instapage_admin_settings_form', $form, $form_state);
    $formErrors = $form_state->getErrors();
    $this->assertCount(0, $formErrors);
    $this->assertEquals($this->email, $form_state->getValue('instapage_user_id'));
    $this->assertEquals($this->token, $form_state->getValue('instapage_plugin_hash'));
  }

  /**
   * Tests the submitForm() method call for logout.
   */
  public function testFormSubmitLogout(): void {
    $this->settingsConfig->set('instapage_user_id', $this->email);
    $this->settingsConfig->set('instapage_user_token', $this->token)->save();
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
    ]);
    $form_state = (new FormState())
      ->setValues([
        'instapage_user_email' => $this->email,
        'instapage_user_password' => $this->password,
      ]);
    $this->formBuilder->submitForm(SettingsForm::class, $form_state);
    $this->settingsConfig = $this->config('instapage.settings');
    $this->assertCount(0, $form_state->getErrors());
    $this->assertEmpty($this->settingsConfig->get('instapage_user_id'));
    $this->assertEmpty($this->settingsConfig->get('instapage_user_token'));
  }

  /**
   * Tests the submitForm() method call for login.
   */
  public function testFormSubmitLogin(): void {
    $_SERVER['SERVER_NAME'] = 'testing';
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"usertoken":"iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3","firstname":"Testing 123","lastname":"UK12345678","email":"testing@testing.com"},"message":"Login succed"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '', '1.1', 'OK'),
    ]);
    $form_state = (new FormState())
      ->setValues([
        'instapage_user_email' => $this->email,
        'instapage_user_password' => $this->password,
        'instapage_user_id' => $this->email,
        'instapage_plugin_hash' => $this->token,
      ]);
    $this->formBuilder->submitForm(SettingsForm::class, $form_state);
    $this->settingsConfig = $this->config('instapage.settings');
    $this->assertCount(0, $form_state->getErrors());
    $this->assertEquals($this->email, $this->settingsConfig->get('instapage_user_id'));
    $this->assertEquals($this->token, $this->settingsConfig->get('instapage_user_token'));
  }

  /**
   * Mocks responses for request method.
   *
   * @param array $responses
   *   Responses that will be executed.
   */
  public function mockRequestMethod(array $responses): void {
    switch (count($responses)) {
      case 0:
        break;

      case 1:
        $this->mockClient
          ->method('request')
          ->willReturn($responses[0]);
        break;

      default:
        $this->mockClient
          ->method('request')
          ->will(call_user_func_array([
            $this,
            'onConsecutiveCalls',
          ], $responses));
        break;
    }
  }

}
