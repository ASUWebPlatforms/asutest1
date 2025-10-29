<?php

namespace Drupal\instapage;

/**
 * Interface for Api service.
 *
 * @package Drupal\media_pixabay\Api
 */
interface ApiInterface {

  /**
   * Instapage API endpoint url.
   */
  const ENDPOINT = 'https://app.instapage.com';

  /**
   * API request method.
   */
  const METHOD = 'POST';

  /**
   * Sends out an API call and returns the results.
   *
   * @param string $action
   *   Action to execute.
   * @param array $headers
   *   Headers to send.
   * @param array $params
   *   Parameters to send.
   *
   * @return array|bool
   *   Response data or FALSE on failure.
   */
  public function createRequest(string $action = '', array $headers = [], array $params = []);

  /**
   * Saves a user in config and registers user through the API.
   *
   * @param string $email
   *   User email.
   * @param mixed $token
   *   Account token.
   */
  public function registerUser(string $email, $token);

  /**
   * Verifies the user email and password.
   *
   * @param string $email
   *   User email.
   * @param string $password
   *   User password.
   *
   * @return array
   *   Result data.
   */
  public function authenticate(string $email, string $password): array;

  /**
   * Retrieves account keys for a token.
   *
   * @param mixed $token
   *   Account token.
   *
   * @return array
   *   Result data.
   */
  public function getAccountKeys($token): array;

  /**
   * Retrieves a list of pages for a token.
   *
   * @param string $token
   *   Account token.
   *
   * @return array|object
   *   Result data.
   */
  public function getPageList(string $token);

  /**
   * Returns encoded account keys.
   *
   * @param string $token
   *   Account token.
   *
   * @return bool|string
   *   Encoded account keys or FALSE on failure.
   */
  public function getEncodedKeys(string $token);

  /**
   * Edits an instapage page.
   *
   * @param string $page_id
   *   Id of page.
   * @param string $path
   *   Path of page.
   * @param string $token
   *   Account token.
   * @param int $publish
   *   Flag whether to publish a page.
   */
  public function editPage(string $page_id, string $path, string $token, int $publish = 1);

  /**
   * Connects current domain to Drupal publishing on Instapage.
   *
   * @param string $token
   *   Account token.
   *
   * @return bool
   *   FALSE on failure, TRUE on success.
   */
  public function connectKeys(string $token): bool;

  /**
   * Fetches the subaccounts from the API.
   *
   * @param string $token
   *   Account token.
   *
   * @return array
   *   Result data.
   */
  public function getSubAccounts(string $token): array;

  /**
   * Fetches the raw subaccounts from the API.
   *
   * @param string $token
   *   Account token.
   *
   * @return array
   *   Result data.
   */
  public function getRawSubAccounts(string $token): array;

}
