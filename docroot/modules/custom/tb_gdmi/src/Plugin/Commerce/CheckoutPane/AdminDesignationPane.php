<?php

namespace Drupal\tb_gdmi\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a admin designation pane.
 *
 * @CommerceCheckoutPane(
 *   id = "admin_designation_pane",
 *   label = @Translation("Admin Designation Pane"),
 * )
 */
class AdminDesignationPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['#attributes']['class'][] = 'container';

    $pane_form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h1 class="title">Admin Designation</h1>',
    ];

    $pane_form['admin_designation'] = [
      '#type' => 'radios',
      '#required' => TRUE,
      '#options' => [
        'self' => 'I will be the administrator of this assessment',
        'other' => 'I will invite another person to administer this assessment'
      ],
      '#default_value' => $this->order->getData('admin_designation') ?? 'self',
    ];

    $pane_form['admins_invitation_designation'] = [
      '#type' => 'container',
      '#theme' => 'admins_invitation_designation',
      '#isDesignation' => TRUE,
      '#message' => \Drupal::config('tb_gdmi.groups_communications')->get(),
      '#email' => $this->order->getData('admin_designation_email'),
      '#admin_designation_participation' => $this->order->getData('admin_designation_participation'),
      '#states' => [
        'visible' => [
          ':input[name="admin_designation_pane[admin_designation]"]' => ['value' => 'other'],
        ],
      ],
      '#attached' => [
        'library' => ['tb_gdmi/admin_designation'],
      ],
    ];

    return $pane_form;
  }

   /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    if ($values['admin_designation'] === 'other') {
      $email = $form_state->getUserInput()['email'];
      if ($email === '' || !\Drupal::service('email.validator')->isValid($email)) {
        $form_state->setErrorByName('email', $this->t('The email address %mail is not valid.', ['%mail' => $email]));
      }
    }
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $values = $form_state->getValue($pane_form['#parents']);
    $prev_selection = $this->order->getData('admin_designation');
    $participants = $this->order->getData('participants');
    $prev_email = $this->order->getData('admin_designation_email');

    $this->order->setData('admin_designation', $values['admin_designation']);

    // User wants to designated another user as admin.
    if ($values['admin_designation'] === 'other') {

      $email = $form_state->getUserInput()['email'];
      $admin_designation_participation = $form_state->getUserInput()['admin_participation'];
      $this->order->setData('admin_designation_email', $email);
      $this->order->setData('admin_designation_participation', $admin_designation_participation);
      
      //The designated admin will be added as participant.
      if ($admin_designation_participation === '1') {

        // User change only email and data needs to be updated.
        if ($prev_selection === 'other' && $participants !== NULL && count($participants) >= 2) {
          if ($prev_email === $participants[1]['email']) {
            $participants[1]['email'] = $email;
            $designated_admin = \Drupal::service('tb_gdmi.gdmi_utils')->getExistingUser($email);
            $participants[1]['first_name'] = $designated_admin !== NULL ? $designated_admin->field_first_name->value : '';
            $participants[1]['last_name'] = $designated_admin !== NULL ? $designated_admin->field_last_name->value : '';
          }
        }
        
        // User comes from self desgination and need to be added as participant.
        if ($participants !== NULL && !$this->findInList($participants, $email)) {
          $designated_admin = \Drupal::service('tb_gdmi.gdmi_utils')->getExistingUser($email);
          $new_element = [
            'email' =>  $email,
            'first_name' => $designated_admin !== NULL ? $designated_admin->field_first_name->value : '',
            'last_name' => $designated_admin !== NULL ? $designated_admin->field_last_name->value : '',
            'role' => 'admin',
            'expansion' => '0'
          ];
          if (count($participants) >= 2) {
            $participants = $this->addAdminToList($participants, $new_element);
          } else {
            $index = count($participants) > 0 ? 1 : 0;
            $participants[$index + 1] = $new_element;
          }
        }
      }

      // Designated admin won't be added as participant.
      if ($admin_designation_participation === '0') {
        if ($participants !== NULL) {
          $this->removeFromList($participants, $email);
        }
      }

      $this->order->setData('participants', $participants);
    }

    // User want to be the designated admin.
    if ($values['admin_designation'] === 'self' && $participants !== NULL) {
      $prev_email = $this->order->getData('admin_designation_email');
      $this->removeFromList($participants, $prev_email);
      $this->order->setData('participants', $participants);
    }
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

  public function addAdminToList($participants, $admin) {
    for ($i = count($participants) - 1; $i >= 1; $i--) {
      $participants[$i + 1] = $participants[$i];
    }
    $participants[1] = $admin;
    return $participants;
  }

  public function removeFromList(&$participants, $email) {
    for ($i = 0; $i < count($participants); $i++) {
      if ($participants[$i]['email'] === $email && $participants[$i]['role'] === 'admin') {
        unset($participants[$i]);
        break;
      }
    }
    $participants = array_values($participants);
  }

  public function findInList($participants, $email) {
    $exist = FALSE;
    for ($i = 0; $i < count($participants); $i++) {
      if ($participants[$i]['email'] === $email && $participants[$i]['role'] === 'admin') {
        $exist = TRUE;
        break;
      }
    }
    return $exist;
  }
}
