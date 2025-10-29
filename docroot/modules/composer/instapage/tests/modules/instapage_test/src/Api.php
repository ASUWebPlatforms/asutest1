<?php

namespace Drupal\instapage_test;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\instapage\ApiInterface;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;

/**
 * Mocked version of Instapage Api service.
 *
 * @package Drupal\instapage_test
 */
class Api implements ApiInterface {

  use StringTranslationTrait;

  /**
   * Http client service.
   *
   * @var \GuzzleHttp\Client
   */
  protected Client $client;

  /**
   * Settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Pages configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * Test configuration variable.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $testConfig;

  /**
   * Api constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory service.
   * @param \GuzzleHttp\Client $client
   *   Http client service.
   */
  public function __construct(ConfigFactory $config, Client $client) {
    $this->config = $config->getEditable('instapage.settings');
    $this->pagesConfig = $config->getEditable('instapage.pages');
    $this->testConfig = $config->getEditable('instapage_test.testing');
    $this->client = $client;
  }

  /**
   * Creates and returns a new instance of the service.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Drupal container service.
   *
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function createRequest(string $action = '', array $headers = [], array $params = []) {
  }

  /**
   * {@inheritdoc}
   */
  public function registerUser($email, $token) {
    $this->config->set('instapage_user_id', $email);
    $this->config->set('instapage_user_token', $token);
    $this->config->save();
    $this->connectKeys($token);
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate($email, $password): array {
    $user = $this->testConfig->get('auth');
    if ($user) {
      $reponse = [
        'body' => '{"success":true,"error":false,"data":{"usertoken":"iuaphdsaihdhjsdikbfhdsjbfhskfius744758ogf83bfi3bbfbf88ob3zbfsdf3","firstname":"Testing 123","lastname":"UK12345678","email":"testing@testing.com"},"message":"Login succed"}',
        'status' => 'OK',
        'code' => 200,
      ];
    }
    else {
      $reponse = FALSE;
    }
    if ($reponse && $reponse['code'] == 200) {
      $decoded = json_decode($reponse['body']);
      return ['status' => 200, 'content' => $decoded->data->usertoken];
    }
    return ['error' => TRUE, 'content' => $this->t('Login failed.')];
  }

  /**
   * {@inheritdoc}
   */
  public function getAccountKeys($token): array {
    $user = $this->testConfig->get('acc_keys');
    if ($user) {
      $response = [
        'body' => '{"success":true,"error":false,"data":{"accountkeys":["auihldailbdaibd287z738g3vb39b3z9bzigb9gf3gbfi3bf83gf8383gh38g8b3","ajsibdu28orh3obfo3bfo38fb3ozbf3izfb3zbf3zb3zbf3ib3ib3zibf3zibf33","ahsuh2o8hc3gtb3z80f38bz3b3oh8v3biz3bfu83hb3zbvhbu83bvz3bvz3bvb3b","fb3ibf389hf3bfz839hbf3bf3ufb3ubf3bf3bu3bf3bfu3bfuz3bz3bf3bf3f3bf"]},"message":"Found 4 keys"}',
        'status' => 'OK',
        'code' => 200,
      ];
    }
    else {
      $response = FALSE;
    }
    if ($response && $response['code'] == 200) {
      $decoded = json_decode($response['body']);
      return ['status' => 200, 'content' => $decoded->data->accountkeys];
    }
    return ['error' => TRUE, 'content' => $this->t('Login failed.')];
  }

  /**
   * {@inheritdoc}
   */
  public function getPageList(string $token) {
    $pages = $this->testConfig->get('page_list');
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      if ($pages) {
        $response = [
          'body' => '{"success":true,"error":false,"data":[{"id":123456,"title":"Testing Page 1","screenshot":"","subaccount":666555}, {"id":234567,"title":"Testing Page 2","screenshot":"","subaccount":666555}]}',
          'status' => 'OK',
          'code' => 200,
        ];
      }
      else {
        $response = [
          'body' => '{"success":true,"error":false,"data":[]}',
          'status' => 'OK',
          'code' => 200,
        ];
      }
      $decoded = json_decode($response['body']);
      $data = [];
      // Fetch available subaccounts from the API.
      $subAccounts = $this->getSubAccounts($token);
      if (!empty($decoded->data)) {
        foreach ($decoded->data as $item) {
          $data[$item->id] = $item->title;
          // If possible add the subaccount label in brackets.
          if (isset($item->subaccount) && array_key_exists($item->subaccount, $subAccounts)) {
            $data[$item->id] .= ' (' . $subAccounts[$item->subaccount] . ')';
          }
        }
      }
      // Save page labels in config.
      $this->pagesConfig->set('page_labels', $data)->save();
      return $decoded;
    }
    return ['error' => TRUE, 'content' => $this->t('Login failed.')];
  }

  /**
   * {@inheritdoc}
   */
  public function getEncodedKeys(string $token) {
    $keys = $this->getAccountKeys($token);
    if (isset($keys['status']) && $keys['status'] == 200) {
      return base64_encode(json_encode($keys['content']));
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function editPage(string $page_id, string $path, string $token, int $publish = 1) {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      // Get existing page paths from config.
      $pages = $this->pagesConfig->get('instapage_pages');
      // Publishing a page.
      if ($publish) {
        $pages[$page_id] = $path;
      }
      else {
        // When unpublishing a page remove it from config.
        if (array_key_exists($page_id, $pages)) {
          unset($pages[$page_id]);
        }
      }
      // Save new page paths to config.
      $this->pagesConfig->set('instapage_pages', $pages)->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function connectKeys(string $token): bool {
    $this->getEncodedKeys($token);
  }

  /**
   * {@inheritdoc}
   */
  public function getSubAccounts(string $token): array {
    $subAccounts = $this->testConfig->get('sub_accounts');
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      if ($subAccounts) {
        $response = [
          'body' => '{"success":true,"error":false,"data":[{"id":666555,"name":"Personal Projects","accountkey":"aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4","pushed":0}]}',
          'status' => 'OK',
          'code' => 200,
        ];
      }
      else {
        $response = [
          'body' => '{"success":true,"error":false,"data":[]}',
          'status' => 'OK',
          'code' => 200,
        ];
      }
      if ($response && $response['code'] == 200) {
        $decode = json_decode($response['body']);
        $accounts = [];
        // Create array of subaccounts and return it.
        foreach ($decode->data as $item) {
          $accounts[$item->id] = $item->name;
        }
        return $accounts;
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRawSubAccounts(string $token): array {
    $subAccounts = $this->testConfig->get('sub_accounts');
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      if ($subAccounts) {
        $response = [
          'body' => '{"success":true,"error":false,"data":[{"id":666555,"name":"Personal Projects","accountkey":"aushdaoibfsibfuowu38th43tb4obg8704hg4bg0404g4gb4b40ghuhgo4hg4gh4","pushed":0}]}',
          'status' => 'OK',
          'code' => 200,
        ];
      }
      else {
        $response = [
          'body' => '{"success":true,"error":false,"data":[]}',
          'status' => 'OK',
          'code' => 200,
        ];
      }
      if ($response && $response['code'] == 200) {
        return json_decode($response['body'], TRUE);
      }
    }
    return [];
  }

}
