<?php

namespace Drupal\quikpay\PluginForm;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\ManualPaymentAddForm;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Config\Config;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\LocalRedirectResponse;
use Drupal\Core\Url;
use Drupal\quikpay\Plugin\Commerce\PaymentGateway\QuikpayRedirectCheckout;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * @see  https://docs.drupalcommerce.org/commerce2/developer-guide/payments/create-payment-gateway/off-site-gateways/off-site-redirect#gathering-data-for-the-request-to-the-payment-provider
 */
class QuikpayPaymentAddForm extends PaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // \Drupal\commerce_payment\PluginForm\PaymentMethodFormBase::buildConfigurationForm
    $payment_gateway_plugin = $this->plugin;
    $config = $payment_gateway_plugin->getConfiguration();
    \Drupal::messenger()->addWarning('QuikpayPaymentAddForm');

    /** @var OrderInterface $order */
    $order = $this->entity->getOrder();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $form['#attached']['library'][] = 'commerce_payment/payment_method_form';
    $form['#tree'] = TRUE;

    // Get data for Nelnet together.
    $billing_profile = $order->getBillingProfile();
    $params = $this->getParams($order, $payment_gateway_plugin, $form['#return_url']);

    $form['payment_details'] = [
      '#parents' => array_merge($form['#parents'], ['payment_details']),
      '#type' => 'container',
      '#payment_method_type' => $payment_method->bundle(),
    ];

    $variables = static::VARIABLES;
    $vars = explode(',', $variables);
    array_push($vars, "hash");
    // Add each parameter as hidden form value. It appears Commerce will submit these for us, correctly.
    foreach ($vars as $key) {
      $form['payment_details'][$key] = array(
        '#type' => 'hidden',
        '#default_value' => $params[$key],
      );
    }

    // @TODO: Check this: how does commerce 2 redirect?
    // Redirect to the Quikpay url
//    $form['#action'] = $url;
    return $form;
  }


  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
//    parent::submitConfigurationForm();
    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = $this->entity;

    $values = $form_state->getValue($form['#parents']);
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsStoredPaymentMethodsInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $this->plugin;
    // The payment method form is customer facing. For security reasons
    // the returned errors need to be more generic.
    try {
      $payment_gateway_plugin->createPaymentMethod($payment_method, $values['payment_details']);
    }
    catch (DeclineException $e) {
      $this->logger->warning($e->getMessage());
      throw new DeclineException(t('We encountered an error processing your payment method. Please verify your details and try again.'));
    }
    catch (PaymentGatewayException $e) {
      $this->logger->error($e->getMessage());
      throw new PaymentGatewayException(t('We encountered an unexpected error processing your payment method. Please try again later.'));
    }  }

  static public function getParams($order, $payment_gateway_plugin, $return_url) {
    // Set up parameters for payload.
    $config = $payment_gateway_plugin->getConfiguration();
    $param['orderType'] = $config['order_type'];
    $param['orderNumber'] = $order->id();
    $param['amount'] = intval($order->getTotalPrice()->getNumber());

    // Not valid.
    // $param['currency_code'] = $order->getTotalPrice()->getCurrencyCode();

//    // Drupal Order ID
//    $param['userChoice2'] = $order->order_id;
//    // Individual order items. Trimmed to 48 characters.
//    $param['userChoice3'] = isset($order_items[0]) ? substr($order_items[0], 0, 48) : '';
//    $param['userChoice4'] = isset($order_items[1]) ? substr($order_items[1], 0, 48) : '';
//    $param['userChoice5'] = isset($order_items[2]) ? substr($order_items[2], 0, 48) : '';
//    $param['userChoice6'] = isset($order_items[3]) ? substr($order_items[3], 0, 48) : '';
//    $param['userChoice7'] = isset($order_items[4]) ? substr($order_items[4], 0, 48) : '';
//    $param['userChoice8'] = isset($order_items[5]) ? substr($order_items[5], 0, 48) : '';
//    $param['userChoice9'] = isset($order_items[6]) ? substr($order_items[6], 0, 48) : '';
//    $param['userChoice10'] = isset($order_items[7]) ? substr($order_items[7], 0, 48) : '';
    // If we have an excess of 8 order items, overwrite last entry.
//    if (isset($order_items[8])) {
//      $param['userChoice10'] = "More... For full details see order ID " . $order->order_id . ".";
//    }

    if ($order->getBillingProfile()) {
      $address = $order->getBillingProfile()->get('address')->first();
      $param['streetOne'] = $address->get('address_line1')->getValue();
      $param['streetTwo'] = $address->get('address_line2')->getValue();
      $param['city'] = $address->get('locality')->getValue();
      $param['state'] = $address->get('administrative_area')->getValue();
      $param['zip'] = $address->get('postal_code')->getValue();
    }

    $param['email'] = $order->getEmail();

    if ($config['quikpay_redirect'] == 'url') {
      // Pass the return URL from Drupal Commerce.
      $param['redirectUrl'] = $return_url;

      // Nelnet Quikpay authorized redirect URLs cannot have a wildcard.
      // The /quikpay/rtpn route is used instead, which redirects to checkout/:order/payment/return
      // The default $return_url is generated by Drupal Commerce, but is dynamic. (checkout/:order/return)
      $param['redirectUrl'] = QuikpayRedirectCheckout::getRedirectUrl();

      $trans_variables = "transactionType,transactionStatus,transactionId,originalTransactionId,transactionTotalAmount,transactionDate,transactionAcountType,transactionEffectiveDate,transactionDescription,transactionResultDate,transactionResultEffectiveDate,transactionResultCode,transactionResultMessage,orderNumber,orderType,orderName,orderDescription,orderAmount,orderFee,orderAmountDue,orderDueDate,orderBalance,orderCurrentStatusBalance,orderCurrentStatusAmountDue";
      $param['redirectUrlParameters'] = $trans_variables;
    }

    // Timestamp in milliseconds.
    list($msecs, $uts) = explode(' ', microtime());
    $timestamp = floor(($uts + $msecs) * 1000);

    // Some configs of PHP can render this as scientific notation. Stop that.
    $timestamp = number_format($timestamp, 0, '.', '');

    $param['timestamp'] = $timestamp;
    // Allow for alter of $variables and $param to add/change data. Be sure to keep the two in sync.
    // Function implementation signature:
    // hook_quikpay_hash_param_alter(&$variables, &$param, $order)
//    drupal_alter('quikpay_hash_param', $variables, $param, $order);

    return $param;
  }

  const VARIABLES = "orderType,orderNumber,amount,streetOne,streetTwo,city,state,zip,email";

}
