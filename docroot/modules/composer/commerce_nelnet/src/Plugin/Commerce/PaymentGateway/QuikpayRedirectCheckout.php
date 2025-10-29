<?php

namespace Drupal\quikpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the QuickPay offsite Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "quikpay_redirect_checkout",
 *   label = @Translation("QuikPay (Redirect to quikpay)"),
 *   display_label = @Translation("QuikPay"),
 *   forms = {
 *     "offsite-payment" = "Drupal\quikpay\PluginForm\QuikpayPaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "mastercard", "visa",
 *   },
 * )
 */
class QuikpayRedirectCheckout extends OffsitePaymentGatewayBase
{

  public function defaultConfiguration()
  {
    $settings = parent::defaultConfiguration();
    $settings['order_type'] = "";
    $settings['quikpay_title'] = "Nelnet QuikPAY Secure Payment Server";
    $settings['quikpay_cc_images'] = array();
    $settings['mode'] = 'test';
    $settings['quikpay_redirect'] = 'rtpn';
    $settings['quikpay_test_pt_key'] = 'key';
    $settings['quikpay_test_rtpn_key'] = 'key';
    $settings['quikpay_prod_rtpn_key'] = 'key';
    $settings['quikpay_test_url'] = "https://uatquikpayasp.com/##ORGNAME##/commerce_manager/payer.do";
    $settings['quikpay_prod_url'] = "https://quikpayasp.com/##ORGNAME##/commerce_manager/payer.do";
    $settings['quikpay_success_text'] = 'Thank you for your payment. You may now view your orders by clicking on the link below.';
    $settings['quikpay_checkout_text'] = 'IMPORTANT! In order to make an online payment a new window will open to receive and process your payment details. You may return to this site once your payment is complete by closing that window. You will receive an email receipt as well as an additional email containing course registration details.';
    $settings['quikpay_checkout_red'] = 'You will be directed to a payment page that will process your payment details. Once the payment is complete, you will automatically return to this site and will be able to view your completed order. You will receive an email receipt as well as an additional email containing course registration details.';
    return $settings;
  }

  /**
   * @return string The absolute URL to the Quikpay RTPN page, to be used for Nelnet redirect URL.
   */
  public static function getRedirectUrl() {
    return Url::fromRoute('quikpay.rtpn', [], ['absolute' => true])->toString();
  }

  public function buildConfigurationForm(array $form, FormStateInterface $form_state)
  {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['redirect'] = array(
      '#type' => 'textfield',
      '#title' => t('Redirect URL'),
      '#default_value' => static::getRedirectUrl(),
      '#disabled' => true,
      '#description' => t('Add this URL to NelNet configuration.'),
    );

    $form['order_type'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY order type'),
      '#default_value' => $this->configuration['order_type'],
    );
    $form['quikpay_title'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY payment method title'),
      '#default_value' => $this->configuration['quikpay_title'],
      '#description' => t('Title for payment method displayed to users.'),
    );
    $form['quikpay_cc_images'] = array(
      '#type' => 'checkboxes',
      '#title' => t('Credit card images to display'),
      '#default_value' => $this->configuration['quikpay_cc_images'],
      '#options' => array(
        'visa' => 'Visa',
        'mastercard' => 'MasterCard',
        'amex' => 'American Express',
        'discover' => 'Discover',
      ),
      '#description' => t('Choose credit card images to display when checking out.'),
    );
    $form['mode'] = array(
      '#type' => 'select',
      '#title' => t('QuikPAY operation mode'),
      '#default_value' => $this->configuration['mode'],
      '#options' => array(
        'test' => t('Test'),
        'prod' => t('Production'),
      ),
      '#description' => t('Global setting for the length of XML feed items that are output by default.'),
    );
    $form['quikpay_redirect'] = array(
      '#type' => 'select',
      '#title' => t('QuikPAY redirect method'),
      '#default_value' => $this->configuration['quikpay_redirect'],
      '#options' => array(
        'rtpn' => t('Real Time Payment Notification'),
        'url' => t('Redirect URL'),
      ),
      '#description' => t('Select whether to use RTPN or the Redirect URL method upon completion of payment.'),
    );
    $form['quikpay_test_pt_key'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY passthrough authentication test key'),
      '#default_value' => $this->configuration['quikpay_test_pt_key'],
    );
    $form['quikpay_test_rtpn_key'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY real-time payment notification test key'),
      '#default_value' => $this->configuration['quikpay_test_rtpn_key'],
    );
    $form['quikpay_prod_pt_key'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY passthrough authentication production key'),
      '#default_value' => $this->configuration['quikpay_prod_pt_key'],
    );
    $form['quikpay_prod_rtpn_key'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY real-time payment notification production key'),
      '#default_value' => $this->configuration['quikpay_prod_rtpn_key'],
    );
    $form['quikpay_test_url'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY test URL'),
      '#default_value' => $this->configuration['quikpay_test_url'],
    );
    $form['quikpay_prod_url'] = array(
      '#type' => 'textfield',
      '#title' => t('QuikPAY production URL'),
      '#default_value' => $this->configuration['quikpay_prod_url'],
    );
    $form['quikpay_success_text'] = array(
      '#type' => 'textarea',
      '#title' => t('Success message'),
      '#default_value' => $this->configuration['quikpay_success_text'],
      '#description' => ('Text to display upon successful completion of payment'),
      '#required' => TRUE
    );
    $checkout_text = 'IMPORTANT! In order to make an online payment a new window will open to receive and process your payment details. You may return to this site once your payment is complete by closing that window. You will receive an email receipt as well as an additional email containing course registration details.';
    $form['quikpay_checkout_text'] = array(
      '#type' => 'textarea',
      '#title' => t('RTPN checkout instructions'),
      '#default_value' => $this->configuration['quikpay_checkout_text'],
      '#description' => t('Instructional text to display below the proceed to checkout link'),
      '#required' => TRUE
    );
    $form['quikpay_checkout_red'] = array(
      '#type' => 'textarea',
      '#title' => t('Redirect URL checkout instructions'),
      '#default_value' => $this->configuration['quikpay_checkout_red'],
      '#description' => t('Instructional text to display below the proceed to checkout link'),
      '#required' => TRUE
    );

    return $form;
  }

  public function submitConfigurationForm(array &$form, FormStateInterface $form_state)
  {
    parent::submitConfigurationForm($form, $form_state);
    $values = $form_state->getValue($form['#parents']);
    $this->configuration['order_type'] = $values['order_type'];
    $this->configuration['quikpay_title'] = $values['quikpay_title'];
    $this->configuration['quikpay_cc_images'] = $values['quikpay_cc_images'];
    $this->configuration['mode'] = $values['mode'];
    $this->configuration['quikpay_test_pt_key'] = $values['quikpay_test_pt_key'];
    $this->configuration['quikpay_test_rtpn_key'] = $values['quikpay_test_rtpn_key'];
    $this->configuration['quikpay_prod_pt_key'] = $values['quikpay_prod_pt_key'];
    $this->configuration['quikpay_prod_rtpn_key'] = $values['quikpay_prod_rtpn_key'];
    $this->configuration['quikpay_test_url'] = $values['quikpay_test_url'];
    $this->configuration['quikpay_prod_url'] = $values['quikpay_prod_url'];
    $this->configuration['quikpay_redirect'] = $values['quikpay_redirect'];
    $this->configuration['quikpay_success_text'] = $values['quikpay_success_text'];
    $this->configuration['quikpay_checkout_text'] = $values['quikpay_checkout_text'];
    $this->configuration['quikpay_checkout_red'] = $values['quikpay_checkout_red'];
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentInterface $payment_method, array $data) {

    // $data should contain information coming back from Nelnet.
    $price = [
      'number' => $data['amount'],
      'currency_code' => $data['currency_code'],
    ];
    $payment_method->setAmount(Price::fromArray($price));
    $payment_method->setRemoteId('1234');
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   * @see https://git.drupalcode.org/project/commerce_square/-/blob/8.x-1.x/src/Plugin/Commerce/PaymentGateway/Square.php?ref_type=heads
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $payment_method = $payment->getPaymentMethod();
    if (!$payment_method || empty($payment_method->getRemoteId())) {
      throw new PaymentGatewayException('Cannot create the payment without the Quikpay order ID.');
    }

    // @TODO: Submit to quikpay.
    $config = $this->getConfiguration();
    $url = $this->getUrl();

    $remote_payment = [];
    $remote_state = 'pending';

    $state = 'pending';
    $payment_amount = Price::fromArray([
      'number' => $remote_payment['amount']['value'],
      'currency_code' => $remote_payment['amount']['currency_code'],
    ]);
    $payment->setAmount($payment_amount);
    $payment->setState($state);
    $payment->setRemoteId($remote_payment['id']);
    $payment->setRemoteState($remote_state);
    $payment->save();
  }


  /**
   * @return string The quikpay API url, based on current "mode".
   */
  public function getUrl() {
    $config = $this->getConfiguration();
    $mode = $config['mode'];
    return $config["quikpay_{$mode}_url"];
  }

  /**
   * Processes the "return" request.
   *
   * This method should only be concerned with creating/completing payments,
   * the parent order does not need to be touched. The order state is updated
   * automatically when the order is paid in full, or manually by the
   * merchant (via the admin UI).
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the request is invalid or the payment failed.
   *
   * @see Drupal\commerce_payment_example\Plugin\Commerce\PaymentGateway\OffsiteRedirect
   */
  public function onReturn(OrderInterface $order, Request $request) {
    if ($request->query->get('transactionResultCode') != 1) {
      throw new PaymentGatewayException($this->t('Payment failed: :message', [
        ':message' => $request->query->get('transactionResultMessage')
      ]));
    }
    $payment_entity_data = [
      'state' => 'authorization',
      'amount' => $order->getBalance(),
      'payment_gateway' => $this->parentEntity->id(),
      'order_id' => $order->id(),
      'remote_id' => $request->query->get('transactionId'),
      'remote_state' => $request->query->get('transactionResultCode'),
    ];
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create($payment_entity_data);
    $payment->save();
  }
}
