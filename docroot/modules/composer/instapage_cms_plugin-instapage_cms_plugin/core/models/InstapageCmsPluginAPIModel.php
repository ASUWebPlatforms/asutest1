<?php

/**
 * Class responsible for communication with Instapage app via API.
 */
class InstapageCmsPluginAPIModel {

  /**
   * @var object API model instance.
   */
  private static $apiModel = null;

  /**
   * Gets the class instance.
   *
   * @return object Class instance.
   */
  public static function getInstance() {
    if (self::$apiModel === null) {
      self::$apiModel = new InstapageCmsPluginAPIModel();
    }

    return self::$apiModel;
  }

  /**
   * Performs the remote post based on selected CMS.
   *
   * @return object Result of the request in unified form.
   */
  public function remotePost($url, $data = array(), $headers = array()) {
    return InstapageCmsPluginConnector::getSelectedConnector()->remotePost($url, $data, $headers);
  }

  /**
   * Performs the request to Instapage pageserver.
   *
   * @param string $url URL of the request.
   * @param string $host Host of the request. It will be used for request header.
   * @param array $cookies Cookie array.
   *
   * @return object $response Request response.
   */
  public function enterpriseCall($url, $host = '', $cookies = false) {
    $connector = InstapageCmsPluginConnector::getSelectedConnector();
    $data = array();
    $headers = array();

    $host = $host ? $host : $connector->getHost();
    $integration = $connector->name;
    $data['integration'] = $integration;
    $data['useragent'] = InstapageCmsPluginHelper::filterInput(INPUT_SERVER, 'HTTP_USER_AGENT');
    $data['ip'] = InstapageCmsPluginHelper::filterInput(INPUT_SERVER, 'REMOTE_ADDR');
    $data['cookies'] = $cookies;
    $data['custom'] = InstapageCmsPluginHelper::filterInput(INPUT_GET, 'custom');
    $data['variant'] = InstapageCmsPluginHelper::filterInput(INPUT_GET, 'variant');
    $data['requestHost'] = $host;
    $headers['integration'] = $integration;
    $headers['x-plugin-version'] = INSTAPAGE_PLUGIN_VERSION;
    $headers['x-cms-version'] = $connector->getCMSVersion();
    $headers['x-instapage-host'] = $host;
    $headers['x-php-version'] = PHP_VERSION_ID;
    $response = $connector->remoteRequest($url, $data, $headers, 'POST');

    InstapageCmsPluginHelper::writeDiagnostics($url, 'Enterprise call URL');
    InstapageCmsPluginHelper::writeDiagnostics($host, 'Enterprise call host');
    InstapageCmsPluginHelper::writeDiagnostics($headers, 'Enterprise call headers');
    InstapageCmsPluginHelper::writeDiagnostics($data, 'Enterprise call data');
    InstapageCmsPluginHelper::writeDiagnostics($response, 'Enterprise call response');

    return $response;
  }

  /**
   * Performs the request to Instapage app.
   *
   * @param string $action API action.
   * @param array $data Data to be passed in the request.
   * @param array $headers Headers for the request.
   * @param string $method Request type. 'POST' ang 'GET' are allowed. Default: 'POST'.
   *
   * @return object $response Request response.
   */
  public function apiCall($action, $data = array(), $headers = array(), $method = 'POST') {
    $integration = InstapageCmsPluginConnector::getSelectedConnector()->name;
    $url = InstapageCmsPluginConnector::getURLWithSelectedProtocol(INSTAPAGE_APP_ENDPOINT . '/' . $action);
    $headers['integration'] = $integration;
    $response = InstapageCmsPluginConnector::getSelectedConnector()->remoteRequest($url, $data, $headers, $method);

    InstapageCmsPluginHelper::writeDiagnostics($method . ' : ' . $url, 'API ' . $action . ' URL');
    InstapageCmsPluginHelper::writeDiagnostics($data, 'API ' . $action . ' data');
    InstapageCmsPluginHelper::writeDiagnostics($headers, 'API ' . $action . ' headers');
    InstapageCmsPluginHelper::writeDiagnostics($response, 'API ' . $action . ' response');

    return (is_array($response) && isset($response['body'])) ? $response['body'] : null;
  }

  /**
   * Authorizes the user based on email and password.
   *
   * @param string $email Email address.
   * @param string password Password.
   *
   * @uses InstapageCmsPluginAPIModel::apiCall() to communicate with Instapage app.
   *
   * @return object App response.
   */
  public function authorise($email, $password) {
    $data = array('email' => $email, 'password' => $password);
    $response = $this->apiCall('page', $data);

    return $response;
  }
}
