<?php

namespace Drupal\quikpay\PluginForm;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\quikpay\Plugin\Commerce\PaymentGateway\QuikpayRedirectCheckout;

/**
 * @see  https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/off-site-gateways/off-site-redirect#gathering-data-for-the-request-to-the-payment-provider
 */
class QuikpayPaymentForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->plugin->getConfiguration();
    $mode = $config['mode'];
    $url = $config["quikpay_{$mode}_url"];
    $pt_key = $config["quikpay_{$mode}_pt_key"];

    /** @var PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $data = QuikpayPaymentAddForm::getParams($payment->getOrder(), $payment_gateway_plugin, $form['#return_url']);

    // Create hash
    $hash_string = "";
    $variables = QuikpayPaymentAddForm::VARIABLES;
    $redirect_method = $config['quikpay_redirect'];

    if ($redirect_method == 'url') {
      $variables .= ",redirectUrl,redirectUrlParameters";
    }
    $variables .= ",timestamp";
    foreach (explode(',', $variables) as $key) {
      $hash_string .= $data[$key] ?? '';
    }

    $data['hash'] = hash('sha256', $hash_string . $pt_key);

    $form = parent::buildConfigurationForm($form, $form_state);
    $form = $this->buildRedirectForm($form, $form_state, $url, $data, PaymentOffsiteForm::REDIRECT_POST);

    // If you need to debug this form, uncomment this to stop the automatic submission.
    // $form['#attached']['library'] = [];

    return $form;
  }
}
