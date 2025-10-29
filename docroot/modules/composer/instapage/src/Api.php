<?php

namespace Drupal\instapage;

use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use GuzzleHttp\ClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Contains all the necessary functions to communicate with Instapage.
 *
 * @package Drupal\instapage
 */
class Api implements ApiInterface {

  use StringTranslationTrait;

  /**
   * Http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $client;

  /**
   * Settings config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $config;

  /**
   * Pages config.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $pagesConfig;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected Request $currentRequest;

  /**
   * Api constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config factory service.
   * @param \GuzzleHttp\ClientInterface $client
   *   Http client service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack service.
   */
  public function __construct(ConfigFactory $config, ClientInterface $client, RequestStack $requestStack) {
    $this->config = $config->getEditable('instapage.settings');
    $this->pagesConfig = $config->getEditable('instapage.pages');
    $this->client = $client;
    $this->currentRequest = $requestStack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function createRequest(string $action = '', array $headers = [], array $params = []) {
    $headers['integration'] = 'drupal';
    try {
      $request = $this->client->request(
        self::METHOD,
        self::ENDPOINT . '/api/plugin/page' . $action,
        [
          'allow_redirects' => [
            'max' => 5,
          ],
          'connect_timeout' => 45,
          'synchronous' => TRUE,
          'version' => '1.0',
          'form_params' => $params,
          'headers' => $headers,
        ]
      );
      if ($request->getStatusCode() === 200) {
        $headers = $request->getHeaders();
        return [
          'body' => (string) $request->getBody(),
          'status' => $request->getReasonPhrase(),
          'code' => $request->getStatusCode(),
          'headers' => $headers,
        ];
      }
      return FALSE;
    }
    catch (\Exception $e) {
      return FALSE;
    }
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
    $reponse = $this->createRequest('', [], [
      'email' => $email,
      'password' => $password,
    ]);
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
    $response = $this->createRequest('/get-account-keys', ['usertoken' => $token], ['ping' => TRUE]);
    if ($response && $response['code'] === 200) {
      $decoded = json_decode($response['body']);
      return ['status' => 200, 'content' => $decoded->data->accountkeys];
    }
    return ['error' => TRUE, 'content' => $this->t('Login failed.')];
  }

  /**
   * {@inheritdoc}
   */
  public function getPageList(string $token) {
    $sub_accounts = $this->getRawSubAccounts($token);
    if (empty($sub_accounts)) {
      return [
        'error' => TRUE,
        'content' => $this->t('Failed to request subaccount list.'),
      ];
    }
    $pages = NULL;
    $data = [];
    $errors = [];
    foreach ($sub_accounts['data'] as $sub_account) {
      $encoded = base64_encode(json_encode([$sub_account['accountkey']]));
      $response = $this->createRequest('/list', ['accountkeys' => $encoded], ['ping' => TRUE]);
      if (!$response) {
        $errors[] = $this->t('Failed to get pages for %subacc.', ['%subacc' => $sub_account['name']]);
        continue;
      }
      $decoded = json_decode($response['body']);
      if (!$decoded->data) {
        continue;
      }

      foreach ($decoded->data as $item) {
        $data[$item->id] = $item->title;
        // If possible add the subaccount label in brackets.
        $data[$item->id] .= ' (' . $sub_account['name'] . ')';
      }

      if ($pages === NULL) {
        $pages = $decoded;
        continue;
      }
      $pages->data = array_merge($pages->data, $decoded->data);
    }

    $this->pagesConfig->set('page_labels', $data)->save();
    return $pages;
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
      $host = $this->currentRequest->getHost();
      $headers = [
        'accountkeys' => $encoded,
      ];
      $params = [
        'page' => $page_id,
        'url' => $host . '/' . $path,
        'publish' => $publish,
      ];
      $response = $this->createRequest('/edit', $headers, $params);
      if ($response && $response['code'] === 200) {
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
  }

  /**
   * {@inheritdoc}
   */
  public function connectKeys(string $token): bool {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      $http_host = $this->currentRequest->getHost();
      $server_name = $this->currentRequest->server->get('SERVER_NAME');
      $domain = $http_host ?? $server_name;
      $headers = [
        'accountkeys' => $encoded,
      ];
      $params = [
        'accountkeys' => $encoded,
        'status' => 'connect',
        'domain' => $domain,
      ];
      $response = $this->createRequest('/connection-status', $headers, $params);
      if ($response && $response['code'] === 200) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getSubAccounts(string $token): array {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      $headers = [
        'accountkeys' => $encoded,
      ];
      $response = $this->createRequest('/get-sub-accounts-list', $headers);
      if ($response && $response['code'] == 200) {
        $decode = json_decode($response['body']);
        $accounts = [];
        // Create array of subaccounts and return it.
        foreach ($decode->data as $item) {
          $accounts[$item->id] = $item->name;
        }
        $this->pagesConfig->set('instapage_subaccounts', $accounts)->save();
        return $accounts;
      }
    }
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getRawSubAccounts(string $token): array {
    $encoded = $this->getEncodedKeys($token);
    if ($encoded) {
      $headers = [
        'accountkeys' => $encoded,
      ];
      $response = $this->createRequest('/get-sub-accounts-list', $headers);
      if ($response && $response['code'] == 200) {
        return json_decode($response['body'], TRUE);
      }
    }
    return [];
  }

}
