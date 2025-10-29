<?php

namespace Drupal\tb_gdmi\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;

/**
 * @CommerceCheckoutFlow(
 *  id = "gdmi_checkout_flow",
 *  label = @Translation("GDMI checkout flow"),
 * )
 */
class GdmiCheckoutFlow extends CheckoutFlowWithPanesBase {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    $steps = [
      'login' => [
        'label' => $this->t('Login'),
        'previous_label' => $this->t('Back'),
        'has_sidebar' => FALSE,
      ],
      'purchaser_participation' => [
        'label' => $this->t('Purchaser Participation'),
        'next_label' => $this->t('Continue to Create Group'),
        'previous_label' => $this->t('Back'),
      ],
      'admin_designation' => [
        'label' => $this->t('Admin Designation'),
        'next_label' => $this->t('Yes'),
        'previous_label' => $this->t('Back'),
      ],
      'group' => [
        'label' => $this->t('Group Details'),
        'next_label' => $this->t('Continue'),
        'previous_label' => $this->t('Back'),
      ],
      'order_information' => [
        'label' => $this->t('Billing information'),
        'has_sidebar' => TRUE,
        'next_label' => $this->t('Checkout'),
        'previous_label' => $this->t('Back'),
      ],
      'review' => [
        'label' => $this->t('Review'),
        'next_label' => $this->t('Continue to review'),
        'has_sidebar' => TRUE,
      ],
      ] + parent::getSteps();

      $steps['payment']['hidden'] = false;
      $steps['payment']['next_label'] = $this->t('Pay');
      $steps['complete']['next_label'] = $this->t('Finish Creating Group');

      return $steps;
  }

}
