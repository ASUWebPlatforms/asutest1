<?php

namespace Drupal\instapage\Controller;

use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Handles instapage paths.
 *
 * @package Drupal\instapage\Controller
 */
class PageDisplayController extends ControllerBase {

  /**
   * Http client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request|null
   */
  protected $request;

  /**
   * PageDisplayController constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   Http client service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Request stack service.
   */
  public function __construct(ClientInterface $client, RequestStack $request) {
    $this->request = $request->getCurrentRequest();
    $this->httpClient = $client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('request_stack')
    );
  }

  /**
   * Returns the page HTML.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Page response.
   */
  public function content(): Response {
    $data = [];
    $method = 'POST';
    $url = 'https://pageserve.co' . $this->request->getRequestUri();
    $integration = 'drupal';
    $host = $this->request->getHost();
    $headers['integration'] = $integration;
    $headers['x-instapage-host'] = $host;
    $headers['x-cms-version'] = \Drupal::VERSION;
    $data['integration'] = $integration;
    $data['useragent'] = $this->request->headers->get('User-Agent');
    $data['ip'] = $this->request->getClientIp();
    $data['requestHost'] = $host;

    try {
      $request = $this->httpClient->request(
        $method,
        $url,
        [
          'allow_redirects' => [
            'max' => 5,
          ],
          'connect_timeout' => 45,
          'synchronous' => TRUE,
          'version' => '1.0',
          'headers' => $headers,
          'form_params' => $data,
        ]
      );

      return new Response($request->getBody()->getContents(), $request->getStatusCode());
    }
    catch (\Exception $e) {
      return new Response($e->getMessage(), $e->getCode());
    }
  }

}
