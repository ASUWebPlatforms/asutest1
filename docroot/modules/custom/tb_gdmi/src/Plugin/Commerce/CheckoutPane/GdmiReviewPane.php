<?php

namespace Drupal\tb_gdmi\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\Review;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom payment review pane.
 */
class GdmiReviewPane extends Review {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    
    $pane_form = parent::buildPaneForm($pane_form, $form_state, $complete_form);

    $members = [];
    $participants = $this->order->getData('participants');
    if ($participants !== NULL) {
      foreach ($participants as $index => $participant) {
        $members[] = [
          'user' => [
            'field_first_name' => ['value' => $participant['first_name']],
            'field_last_name' => ['value' => $participant['last_name']],
            'mail' => ['value' => $participant['email']],
          ]
        ];
      }
  
      $pane_form['participants_list'] = [
        '#theme' => 'group_participants_list',
        '#members' => $members,
        '#edit_btn' => TRUE,
        '#order_id' => $this->order->id(),
      ];
    }
  
    return $pane_form;
  }

}
