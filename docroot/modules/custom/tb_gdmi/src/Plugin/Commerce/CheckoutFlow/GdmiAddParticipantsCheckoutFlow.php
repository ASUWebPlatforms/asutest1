<?php

namespace Drupal\tb_gdmi\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;

/**
 * @CommerceCheckoutFlow(
 *  id = "gdmi_add_participants_checkout_flow",
 *  label = @Translation("GDMI add participants checkout flow"),
 * )
 */
class GdmiAddParticipantsCheckoutFlow extends CheckoutFlowWithPanesBase {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    $steps = [
      'group' => [
        'label' => $this->t('Additional Participants'),
        'next_label' => $this->t('Yes'),
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

      return $steps;
  }

}
