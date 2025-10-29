<?php

namespace Drupal\quikpay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentReceiveForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quikpay\Plugin\Commerce\PaymentGateway\QuikpayRedirectCheckout;

/**
 * @see  https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/off-site-gateways/off-site-redirect#gathering-data-for-the-request-to-the-payment-provider
 */
class QuikpayPaymentReceiveForm extends PaymentReceiveForm {

}
