<?php

namespace Drupal\quikpay\Controller;

use Drupal\commerce_order\Entity\Order;
use Drupal\Component\Utility\Html;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use http\Client\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Provides route responses for the Example module.
 */
class QuikPayController extends ControllerBase
{

  /**
   * Return URL /quikpay/rtpn.
   * Drupal Commerce has its own payment return page. This controller simply redirects to it.
   *
   * @return RedirectResponse
   */
  function Rtpn()
  {
    $route_params = [
      'commerce_order' => \Drupal::request()->query->get('orderNumber'),
      'step' => 'payment'
    ];
    $redirect_url = Url::fromRoute('commerce_payment.checkout.return', $route_params)->toString()  . '?' . \Drupal::request()->getQueryString();
    \Drupal::messenger()->addStatus(\Drupal::request()->query->get('transactionResultMessage'));
    return new RedirectResponse($redirect_url);
  }
}
