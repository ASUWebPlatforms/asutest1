<?php

namespace Drupal\Tests\instapage\Kernel;

use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormState;
use Drupal\instapage\Form\PageEditForm;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Tests the PageEditForm form.
 *
 * @group instapage
 *
 * @package Drupal\Tests\instapage\Kernel
 */
class PageEditFormTest extends KernelTestBase {

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
   * Mocked Client service variable.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $mockClient;

  /**
   * Mocked RequestStack service variable.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $mockRequestStack;

  /**
   * Testing token variable.
   *
   * @var string
   */
  protected string $token;

  /**
   * Testing email variable.
   *
   * @var string
   */
  protected string $email;

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
    'system',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->mockClient = $this->createMock(Client::class);
    $this->mockClient
      ->method('request')
      ->will($this->onConsecutiveCalls(
        new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
        new Response(200, [], '{"success":true,"error":false}', '1.1', 'OK')
      ));
    $this->mockRequestStack = $this->createMock(RequestStack::class);
    $this->mockRequestStack
      ->method('getCurrentRequest')
      ->willReturn(new Request([], [], ['instapage_id' => '234567'], [], []));
    $this->container->set('http_client', $this->mockClient);
    $this->container->set('request_stack', $this->mockRequestStack);
    $this->token = 'iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3';
    $this->email = 'testing@testing.com';
    $this->settingsConfig = $this->config('instapage.settings');
    $this->pagesConfig = $this->config('instapage.pages');
    $this->formBuilder = $this->container->get('form_builder');
  }

  /**
   * Tests the buildForm() method.
   */
  public function testPageEditFormBuild(): void {
    $this->settingsConfig->set('instapage_user_token', $this->token);
    $this->settingsConfig->set('instapage_user_id', $this->email)->save();
    $this->pagesConfig->set('page_labels', [
      123456 => 'Testing page 1',
      234567 => 'Testing page 2',
    ]);
    $this->pagesConfig->set('instapage_pages', [
      123456 => 'testing-path-1',
      234567 => 'testing-path-2',
    ])->save();
    $form_state = new FormState();
    $form = $this->formBuilder->buildForm(PageEditForm::class, $form_state);
    $this->assertNotNull($form);
    $this->assertCount(0, $form_state->getErrors());
    $this->assertArrayHasKey('label', $form);
    $this->assertEquals('Testing page 2', $form['label']['#markup']);
    $this->assertArrayHasKey('path', $form);
    $this->assertEquals('testing-path-2', $form['path']['#default_value']);
    $this->assertArrayHasKey('submit', $form);
    $this->assertArrayHasKey('cancel', $form);
  }

  /**
   * Tests the submitForm() method.
   */
  public function testPageEditFormSubmit(): void {
    $this->settingsConfig->set('instapage_user_token', $this->token);
    $this->settingsConfig->set('instapage_user_id', $this->email)->save();
    $this->pagesConfig->set('page_labels', [
      123456 => 'Testing page 1',
      234567 => 'Testing page 2',
    ]);
    $pagePaths = [
      123456 => 'testing-path-1',
      234567 => 'testing-path-2',
    ];
    $this->pagesConfig->set('instapage_pages', $pagePaths)->save();
    $form_state = (new FormState())
      ->setTriggeringElement([
        '#parents' => [
          'submit',
        ],
      ])
      ->setValue('path', 'testing-path-3');
    $pagePaths[234567] = 'testing-path-3';
    $this->formBuilder->submitForm(PageEditForm::class, $form_state);
    $this->pagesConfig = $this->config('instapage.pages');
    $this->assertCount(0, $form_state->getErrors());
    $this->assertEquals($pagePaths, $this->pagesConfig->get('instapage_pages'));
  }

}
