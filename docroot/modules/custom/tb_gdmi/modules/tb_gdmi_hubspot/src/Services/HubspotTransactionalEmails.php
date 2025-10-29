<?php

namespace Drupal\tb_gdmi_hubspot\Services;

use Drupal\Core\Config\ConfigFactoryInterface;
use SevenShores\Hubspot\Factory;

/**
 *  GDMI Hubspot Transactional Email Service.
 */
class HubspotTransactionalEmails {

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a HubspotTransactionalEmails object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
  }
  
  public function testEmail() {
    $access_token = $this->configFactory->get('tb_gdmi_hubspot.settings')->get('access_token');
    $hubspot = Factory::createWithOAuth2Token($access_token);
    $hubspot->transactionalEmail()->send(165207647778, [
      'to' => 'lester.barahona@idx.inc',
    ]);
  }

  public function sendEmail($to = 'lester.barahona@idx.inc', $id = 165207647778, $params = []) {
    $access_token = $this->configFactory->get('tb_gdmi_hubspot.settings')->get('access_token');
    $hubspot = Factory::createWithOAuth2Token($access_token);
    $response = $hubspot->transactionalEmail()->send($id, ['to' => $to], [], $params);
    return $response->toArray();
  }

}