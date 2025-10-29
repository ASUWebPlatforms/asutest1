<?php

namespace Drupal\tb_gdmi\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "purchaser_participation_pane",
 *   label = @Translation("Purchaser Participation Pane"),
 * )
 */
class PurchaserParticipationPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['#attributes']['class'][] = 'container';

    $pane_form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h1>Purchaser Participation</h1>',
    ];

    $pane_form['subtitle'] = [
      '#type' => 'markup',
      '#markup' => '<h3>Are you going to be a participant in taking the GDMI?</h3>',
    ];

    $pane_form['paragraph_one'] = [
      '#type' => 'markup',
      '#markup' => '<p>Select “YES” if you plan on taking the GDMI yourself <em>(your information will be auto-populated)</em>.</p>',
    ];

    $pane_form['paragraph_two'] = [
      '#type' => 'markup',
      '#markup' => '<p>Select “NO” if you do not plan on taking the GDMI yourself.</p>',
    ];

    $complete_form['actions']['no_button'] = [
      '#type' => 'submit',
      '#value' => $this->t('No'),
      '#attributes' => ['data-no-button' => 'true'],
      '#attached' => [
        'library' => [
          'tb_gdmi/purchaser_participation',
        ],
      ]
    ];

    $pane_form['no_button_clicked'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    
    return $pane_form;
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $purchaser_participation = 
        boolval($form_state->getUserInput()['purchaser_participation_pane']['no_button_clicked'])
        ?? NULL;

    if (!empty($purchaser_participation) && $purchaser_participation) {
      $this->order->setData('purchaser_participation', FALSE);
    } else {
      $this->order->setData('purchaser_participation', TRUE);
    }
    $this->order->save();
  }

  public function isVisible() {
    if ($this->order->bundle() === 'gdmi_expand_participants') {
      return FALSE;
    }

    $items = $this->order->getItems();
    $item = reset($items);
    $purchased_entity = $item->getPurchasedEntity();
    $type = $purchased_entity->attribute_assessment_type->entity->label();

    return strtolower($type) === 'group';
  }
}
