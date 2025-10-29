<?php

namespace Drupal\Tests\instapage\Kernel;

use Drupal\Core\Config\Config;
use Drupal\instapage\ApiInterface;
use Drupal\KernelTests\KernelTestBase;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the Api service.
 *
 * @group instapage
 */
class ApiTest extends KernelTestBase {

  /**
   * Api service variable.
   *
   * @var \Drupal\instapage\ApiInterface
   */
  protected ApiInterface $api;

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
    $this->settingsConfig = $this->config('instapage.settings');
    $this->pagesConfig = $this->config('instapage.pages');
    $this->mockClient = $this->createMock(Client::class);
    $this->container->set('http_client', $this->mockClient);
    $this->api = $this->container->get('instapage.api');
    $this->token = 'iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3';
    $this->email = 'testing@testing.com';
    $this->password = '123testing123';
  }

  /**
   * Tests the successful call of createRequest method.
   */
  public function testCreateRequestSuccess(): void {
    $this->mockRequestMethod([new Response(200, [], '{}', '1.1', 'OK')]);
    $response = $this->api->createRequest();
    $this->assertEquals('OK', $response['status']);
    $this->assertEquals(200, $response['code']);
  }

  /**
   * Tests the failed call of createRequest method.
   */
  public function testCreateRequestFail(): void {
    $this->mockRequestMethod([
      new Response(404, [], '{"success":true,"error":false}', '1.1', 'Not Found'),
    ]);
    $response = $this->api->createRequest();
    $this->assertFalse($response);
  }

  /**
   * Tests the registerUser method.
   */
  public function testRegisterUser(): void {
    $_SERVER['SERVER_NAME'] = 'testing';
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '', '1.1', 'OK'),
    ]);
    $this->api->registerUser($this->email, $this->token);
    $this->settingsConfig = $this->config('instapage.settings');
    $this->assertEquals($this->email, $this->settingsConfig->get('instapage_user_id'));
    $this->assertEquals($this->token, $this->settingsConfig->get('instapage_user_token'));
  }

  /**
   * Tests the successful call of authenticate method.
   */
  public function testAuthenticateSuccess(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"usertoken":"iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3","firstname":"Testing 123","lastname":"UK12345678","email":"testing@testing.com"},"message":"Login succed"}', '1.1', 'OK'),
    ]);
    $response = $this->api->authenticate($this->email, $this->password);
    $this->assertEquals([
      'status' => 200,
      'content' => $this->token,
    ], $response);
  }

  /**
   * Tests the failed call of authenticate method.
   */
  public function testAuthenticateFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '{"success":true,"error":false}', '1.1', 'Bad Request'),
    ]);
    $response = $this->api->authenticate($this->email, $this->password);
    $this->assertEquals([
      'error' => TRUE,
      'content' => 'Login failed.',
    ], $response);
  }

  /**
   * Tests the successful call of getAccountKeys method.
   */
  public function testGetAccountKeysSuccess(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
    ]);
    $response = $this->api->getAccountKeys($this->token);
    $this->assertEquals([
      'status' => 200,
      'content' => [
        "auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3",
        "ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33",
        "ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b",
        "fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf",
      ],
    ], $response);
  }

  /**
   * Tests the failed call of getAccountKeys method.
   */
  public function testGetAccountKeysFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '{"success":true,"error":false}', '1.1', 'Bad Request'),
    ]);
    $response = $this->api->getAccountKeys($this->token);
    $this->assertEquals([
      'error' => TRUE,
      'content' => 'Login failed.',
    ], $response);
  }

  /**
   * Tests the successful call of getPageList method.
   */
  public function testGetPageListSuccess(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":[{"id":666555,"name":"Personal Projects","accountkey":"aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4","pushed":0}]}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":[{"id":123456,"title":"Testing Page 1","screenshot":"","subaccount":666555}]}', '1.1', 'OK'),
    ]);
    $response = $this->api->getPageList($this->token);
    $this->assertEquals((object) [
      'success' => TRUE,
      'error' => FALSE,
      'data' => [
        (object) [
          'id' => 123456,
          'title' => "Testing Page 1",
          'screenshot' => "",
          'subaccount' => 666555,
        ],
      ],
    ], $response);
  }

  /**
   * Tests the failed call of getPageList method.
   *
   * REASON: getEncodedKeys() fails.
   */
  public function testGetPageListFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '', '1.1', 'Bad request'),
      new Response(200, [], '{"success":true,"error":false,"data":[{"id":123456,"title":"Testing Page 1","screenshot":"","subaccount":666555}]}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":[{"id":666555,"name":"Personal Projects","accountkey":"aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4","pushed":0}]}', '1.1', 'OK'),
    ]);
    $response = $this->api->getPageList($this->token);
    $this->assertEquals([
      'error' => TRUE,
      'content' => 'Failed to request subaccount list.',
    ], $response);
  }

  /**
   * Tests the failed call of getPageList method.
   *
   * REASON: createRequest() fails.
   */
  public function testGetPageListFailedRequestingList(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(400, [], '{"success":false,"error":true}', '1.1', 'Bad request'),
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":[{"id":666555,"name":"Personal Projects","accountkey":"aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4","pushed":0}]}', '1.1', 'OK'),
    ]);
    $response = $this->api->getPageList($this->token);
    $this->assertEquals([
      'error' => TRUE,
      'content' => 'Failed to request subaccount list.',
    ], $response);
  }

  /**
   * Tests the successful call of getEncodedKeys method.
   */
  public function testGetEncodedKeysSuccess(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
    ]);
    $response = $this->api->getEncodedKeys($this->token);
    $this->assertEquals(base64_encode(json_encode([
      'auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3',
      'ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33',
      'ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b',
      'fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf',
    ])), $response);
  }

  /**
   * Tests the failed call of getEncodedKeys method.
   */
  public function testGetEncodedKeysFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '', '1.1', 'Bad request'),
    ]);
    $response = $this->api->getEncodedKeys($this->token);
    $this->assertFalse($response);
  }

  /**
   * Tests the successful call of editPage method.
   */
  public function testEditPageSuccessPublish(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false}', '1.1', 'OK'),
    ]);
    $this->pagesConfig->clear('instapage_pages')->save();
    $this->api->editPage('123123', 'testing-path', $this->token);
    $this->assertArrayHasKey(123123, $this->config('instapage.pages')->get('instapage_pages'));
  }

  /**
   * Tests the failed call of editPage method.
   *
   * REASON: method getEncodedKeys() fails.
   */
  public function testEditPageFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '{"success":false,"error":true}', '1.1', 'Bad request'),
      new Response(200, [], '{"success":true,"error":false}', '1.1', 'OK'),
    ]);
    $this->pagesConfig->clear('instapage_pages')->save();
    $this->api->editPage('123123', 'testing-path', $this->token);
    $this->assertNull($this->config('instapage.pages')->get('instapage_pages'));
  }

  /**
   * Tests the failed call of editPage method.
   *
   * REASON: method createRequest() fails.
   */
  public function testEditPageFailedEditRequest(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(400, [], '{"success":true,"error":false}', '1.1', 'Bad request'),
    ]);
    $this->pagesConfig->set('instapage_pages', [123123 => 'testing-path'])->save();
    $pages = $this->pagesConfig->get('instapage_pages');
    $this->api->editPage('123123', 'testing-path-2', $this->token);
    $this->assertEquals($pages, $this->config('instapage.pages')->get('instapage_pages'));
  }

  /**
   * Tests the successful call of connectKeys method.
   */
  public function testConnectKeysSuccess(): void {
    $_SERVER['SERVER_NAME'] = 'testing';
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false}', '1.1', 'OK'),
    ]);
    $response = $this->api->connectKeys($this->token);
    $this->assertTrue($response);
  }

  /**
   * Tests the failed call of connectKeys method.
   *
   * REASON: getEncodedKeys() fails.
   */
  public function testConnectKeysFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '{"success":false,"error":true}', '1.1', 'Bad request'),
      new Response(200, [], '{"success":true,"error":false}', '1.1', 'OK'),
    ]);
    $response = $this->api->connectKeys($this->token);
    $this->assertNotNull($response);
    $this->assertFalse($response);
  }

  /**
   * Tests the failed call of connectKeys method.
   *
   * REASON: createRequest() fails.
   */
  public function testConnectKeysFailedConnectingStatus(): void {
    $_SERVER['SERVER_NAME'] = 'testing';
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(400, [], '{"success":false,"error":true}', '1.1', 'Bad request'),
    ]);
    $response = $this->api->connectKeys($this->token);
    $this->assertNotNull($response);
    $this->assertFalse($response);
  }

  /**
   * Tests the successful call of getSubAccounts method.
   */
  public function testGetSubAccountsSuccess(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":[{"id":666555,"name":"Personal Projects","accountkey":"aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4","pushed":0}]}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(200, [], '{"success":true,"error":false,"data":[{"id":666555,"name":"Personal Projects","accountkey":"aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4","pushed":0}]}', '1.1', 'OK'),
    ]);
    $response = $this->api->getSubAccounts($this->token);
    $this->assertEquals('Personal Projects', $response[666555]);

    // Works the same as getSubAccounts, just doesn't reformat response.
    $response = $this->api->getRawSubAccounts($this->token);
    $this->assertEquals([
      'success' => TRUE,
      'error' => FALSE,
      'data' => [
        [
          'id' => 666555,
          'name' => "Personal Projects",
          'accountkey' => "aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4",
          'pushed' => 0,
        ],
      ],
    ], $response);
  }

  /**
   * Tests the failed call of getSubAccounts method.
   *
   * REASON: getEncodedKeys() fails.
   */
  public function testGetSubAccountsFailed(): void {
    $this->mockRequestMethod([
      new Response(400, [], '{"success":false,"error":true}', '1.1', 'Bad request'),
      new Response(400, [], '{"success":false,"error":true}', '1.1', 'Bad request'),
    ]);
    $response = $this->api->getSubAccounts($this->token);
    $this->assertNotNull($response);
    $this->assertEmpty($response);

    // Works the same as getSubAccounts, just doesn't reformat response.
    $response = $this->api->getRawSubAccounts($this->token);
    $this->assertNotNull($response);
    $this->assertEmpty($response);
  }

  /**
   * Tests the failed call of getSubAccounts method.
   *
   * REASON: createRequest() fails.
   */
  public function testGetSubAccountsFailedRequestingList(): void {
    $this->mockRequestMethod([
      new Response(200, [], '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}', '1.1', 'OK'),
      new Response(400, [], '{"success":false,"error":true}', '1.1', 'Bad request'),
    ]);
    $response = $this->api->getSubAccounts($this->token);
    $this->assertNotNull($response);
    $this->assertEmpty($response);
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
