<?php

namespace Drupal\tb_gdmi\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a custom message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "group_details_pane",
 *   label = @Translation("Group Details Pane"),
 * )
 */
class GroupDetailsPane extends CheckoutPaneBase {

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $order_type = $this->order->bundle();

     /** @var \Drupal\group\Entity\GroupInterface $group */
    $group = $order_type === 'default' ? NULL : $this->entityTypeManager->getStorage('group')->load($this->order->getData('group_id'));

    $pane_form['#attributes']['class'][] = 'container';
    $pane_form['#tree'] = TRUE;

    $pane_form['title'] = [
      '#type' => 'markup',
      '#markup' => '<h1>' . $this->getTitle() . '</h1>',
    ];

    // Group details.
    if ($order_type === 'default') {
      $pane_form['group_wrapper'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['participants-wrapper'],
        ],
      ];
  
      $pane_form['group_wrapper']['subtitle'] = [
        '#type' => 'markup',
        '#markup' => '<h2 class="sub-heading">Group Details</h2>',
      ];
  
      $pane_form['group_wrapper']['organization'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Organization'),
        '#default_value' => $this->order->getData('organization'),
        '#description' => $this->t('The name of the organization your Group belongs to.<br/><i>Ex. Thunderbird School of Global Management.</i>'),
        '#required' => TRUE,
      ];
  
      $pane_form['group_wrapper']['group_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Group Name'),
        '#default_value' => $this->order->getData('group_name'),
        '#description' => $this->t('This can be any name representative of the group.<br/><i>Ex. Accounting Department <b>or</b> Class of 2028</i>'),
        '#required' => TRUE,
      ];
    }

    // Participants.
    $pane_form['participants_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['participants-wrapper'],
      ],
    ];

    $pane_form['participants_wrapper']['subtitle'] = [
      '#type' => 'markup',
      '#markup' => '<h2 class="sub-heading">' . $this->getSubtitle($group) . '</h2>',
    ];

    $pane_form['participants_wrapper']['description'] = [
      '#type' => 'markup',
      '#markup' => '<p class="description">' . $this->getDescription() . '</p>',
    ];

    $pane_form['upload_by_file']['subtitle'] = [
      '#type' => 'markup',
      '#markup' => '<h6>Bulk upload of participants by CSV file</h6>',
    ];

    $pane_form['upload_by_file']['description'] = [
      '#type' => 'markup',
      '#markup' => '<p class="upload-description"><a href="https://thunderbird.asu.edu/sites/default/files/2025-02/GDMI%20Group%20Participant%20Template.xlsx">Click here</a> to download a template to use to add multiple participants to your group. Please note it is imperative that you keep the column titles from left-to-right, as <strong>First Name, Last Name,</strong> and <strong>Email.</strong> Please add in your desired participants’ unique identifying information. For every participant you wish to add using this bulk upload option, add their respective <strong>First Name, Last Name,</strong> and <strong>Email</strong> to the correct columns and within their own row. Please <a href="https://thunderbird.asu.edu/sites/default/files/2025-02/GDMI%20Group%20Participant%20Template.xlsx">download the template</a> and <strong>PLEASE SAVE FILE AS A CSV</strong> file before attempting to attach below. For examples of this format on Microsoft Excel and on Google Sheets, click “SHOW EXAMPLES” near the upload CSV List button.</p>',
    ];

    $pane_form['upload_by_file']['prefix'] = [
      '#type' => 'markup',
      '#markup' => '
      <div class="upload"><div class="upload-content d-flex"><div class="upload-file-wrapper">',
    ];

    $csv_upload_location = 'public://gdmi/csv-files/';
    $file_system = \Drupal::service('file_system');
    $file_system->prepareDirectory($csv_upload_location, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $file_system->chmod($csv_upload_location, 0777);

    $pane_form['upload_by_file']['upload_file'] = [
      '#title' => $this->t('UPLOAD CSV LIST'),
      '#type' => 'managed_file',
      '#default_value' => $this->order->getData('csv_file'),
      '#upload_location' => $csv_upload_location,
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
    ];

    $pane_form['upload_by_file']['suffix'] = [
      '#type' => 'markup',
      '#markup' => '
      </div><a href="#" class="upload-examples-toggle">SHOW EXAMPLES</a></div><div class="upload-examples"><div class="d-flex flex-column flex-lg-row mx-auto"><div class="d-flex flex-column mx-auto text-center"><h6>Microsoft Excel</h6><img src="/themes/custom/thunderbird/assets/img/screenshot-ms.png"/></div><div class="d-flex flex-column mx-auto text-center"><h6>Google Sheets</h6><img src="/themes/custom/thunderbird/assets/img/screenshot-google.png"/></div></div></div></div>',
    ];
    
    $pane_form['person_details'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['person-details-wrapper']],
      '#attached' => [
        'library' => ['tb_gdmi/participants'],
      ],
    ];    
    
    /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->entityTypeManager->getStorage('user')->load(\Drupal::currentUser()->id());
    $purchaser_participation = $this->order->getFields()['data'][0]->purchaser_participation ?? FALSE;
    $participants = $this->order->getData('participants');

    if ($group !== NULL) {
      $group_members = $group->getMembers(['gdmi-participant']);
      foreach ($group_members as $index => $member) {
        $user = $member->getUser();
        $first_name = $user->get('field_first_name')->getValue()[0]['value'];
        $last_name = $user->get('field_last_name')->getValue()[0]['value'];
        $email = $user->getEmail();
        $pane_form['person_details'][$index] = $this->addParticipantItem($index, $first_name, $last_name, $email, TRUE);
      }
    }

    if ($participants !== NULL && $order_type === 'default') {
      $designated_admin_email =  $this->order->getData('admin_designation_email');
      $i = 0;
      foreach ($participants as $index => $pariticipant) {
        $isDesignedAdmin = isset($pariticipant['role']) && $pariticipant['role'] === 'admin';
        $disabled = $pariticipant['email'] === $current_user->getEmail();
        if ($isDesignedAdmin) {
          $disabled = $this->getDesignatedAdminDisabled($designated_admin_email)['disabled'];
        }
        $pane_form['person_details'][$i] = $this->addParticipantItem($i, $pariticipant['first_name'], $pariticipant['last_name'], $pariticipant['email'], $disabled, $isDesignedAdmin, $isDesignedAdmin ? 'admin' : 'participant');
        $i++;
      }
    }

    if ($participants !== NULL && $order_type === 'gdmi_expand_participants') {
      $i = 0;
      foreach ($participants as $index => $pariticipant) {
        if (!isset($pariticipant['expansion']) || $pariticipant['expansion'] === '1') {
          $isDesignedAdmin = isset($pariticipant['role']) && $pariticipant['role'] === 'admin';
          $disabled = $pariticipant['email'] === $current_user->getEmail();
          $pane_form['person_details'][$i] = $this->addParticipantItem($i, $pariticipant['first_name'], $pariticipant['last_name'], $pariticipant['email'], $disabled, $isDesignedAdmin, $isDesignedAdmin ? 'admin' : 'participant');
        }
        $i++;
      }
    } 
    
    if ($participants === NULL && $group === NULL) {
      $lines = $purchaser_participation ? 2 : 1;
      $admin_designation_participation = $this->order->getData('admin_designation_participation');
      $admin_designation = $this->order->getData('admin_designation');
      $designated_admin_pariticpation = $admin_designation === 'other' && $admin_designation_participation === '1';
      $lines = $designated_admin_pariticpation ? ($lines + 1) : $lines;
      for ($i = 0; $i < $lines; $i++) {
        if ($purchaser_participation && $i === 0) {
          $first_name = $current_user->get('field_first_name')->getValue()[0]['value'];
          $last_name = $current_user->get('field_last_name')->getValue()[0]['value'];
          $email = $current_user->getEmail();
          $pane_form['person_details'][$i] = $this->addParticipantItem($i, $first_name, $last_name, $email, TRUE);
        } elseif (($designated_admin_pariticpation && !$purchaser_participation  && $i === 0) || ($designated_admin_pariticpation && $purchaser_participation && $i === 1)) {
          $designated_admin_email =  $this->order->getData('admin_designation_email');
          $designated_data = $this->getDesignatedAdminDisabled($designated_admin_email);
          $pane_form['person_details'][$i] = $this->addParticipantItem($i, $designated_data['first_name'], $designated_data['last_name'], $designated_admin_email, $designated_data['disabled'], TRUE, 'admin');
        } else {
          $pane_form['person_details'][$i] = $this->addParticipantItem($i);
        }
      }
    }
  
    return $pane_form;
  }

  /**
   * {@inheritdoc}
   */
  public function validatePaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {
    $groupStorage = $this->entityTypeManager->getStorage('group');

    $input = $form_state->getUserInput();
    if (isset($input['group_details_pane']['person_details'])) {
      $participants = array_filter($input['group_details_pane']['person_details'], function($item) { return isset($item['email']);});
      if (empty($participants)) {
        $form_state->setError($pane_form['participants_wrapper'], $this->t('You must add at least one participant.'));
      }
    }

    if ($this->order->bundle() === 'default') {
      $group_name = $form_state->getValue(['group_details_pane','group_wrapper', 'group_name']);
      $organization_name = $form_state->getValue(['group_details_pane','group_wrapper', 'organization']);
      $existing_group = $groupStorage->loadByProperties(['label' => $group_name, 'field_organization' => $organization_name, 'type' => 'gdmi']);
      if (!empty($existing_group)) {
        $form_state->setError($pane_form['group_wrapper']['group_name'], $this->t('Group with that name to the same organization already exist.'));
      }
    }

    if ($this->order->bundle() === 'gdmi_expand_participants') {
      $group_id = $this->order->getData('group_id');
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $groupStorage->load($group_id);
      $input = $form_state->getUserInput();
      if (isset($input['group_details_pane']['person_details']) && $group !== NULL) {
        $new_participants = array_filter($input['group_details_pane']['person_details'], function($item) { return isset($item['email']) && (isset($item['expansion']) && $item['expansion'] === '1');});
        foreach ($new_participants as $participant) {
          $user = \Drupal::service('tb_gdmi.gdmi_utils')->getExistingUser($participant['email']);
          if ($user !== NULL) {
            $member = $group->getMember($user);
            if ($member !== NULL) {
              $form_state->setError($pane_form['participants_wrapper'], $this->t('User with email @email already exist in the group.', ['@email' => $participant['email']]));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitPaneForm(array &$pane_form, FormStateInterface $form_state, array &$complete_form) {

    $participants = [];
    $values = [
      'csv_file' => $form_state->getValue(['group_details_pane', 'upload_by_file', 'upload_file']),
      'participants' => $participants
    ];

    if ($this->order->bundle() === 'default') {
      $values['group_name'] = $form_state->getValue(['group_details_pane', 'group_wrapper', 'group_name']);
      $values['organization_name'] = $form_state->getValue(['group_details_pane', 'group_wrapper', 'organization']);
    }

    $input = $form_state->getUserInput();
    if (isset($input['group_details_pane']['person_details'])) {
      $extra_participants = array_filter($input['group_details_pane']['person_details'], function($item) { return isset($item['email']);});
      $values['participants'] = array_merge($participants, $extra_participants);
    }

    // Store the values in the order.
    $this->createParticipants($values);

  }

  public function isVisible() {
    
    if ($this->order->bundle() === 'gdmi_expand_participants') {
      return TRUE;
    }

    $items = $this->order->getItems();
    $item = reset($items);
    $purchased_entity = $item->getPurchasedEntity();
    $type = $purchased_entity->attribute_assessment_type->entity->label();

    return strtolower($type) === 'group';

  }

  /**
   * Create participants to be added to the order.
   * 
   */
  public function createParticipants($values) {
    
    if ($this->order->bundle() === 'default') {
      $this->order->setData('organization', $values['organization_name']);
      $this->order->setData('group_name', $values['group_name']);
    }
    
    $this->order->setData('csv_file', $values['csv_file']);
    $participants = array_filter($values['participants'], function($item) { return $item['email'] !== ''; });
    $this->order->setData('participants', $participants);
  }

  /**
   * Add participant item to the list.
   */
  public function addParticipantItem($i, $first_name = '', $last_name = '', $email = '', $disabled = FALSE, $disabledEmail = FALSE, $role = 'participant') {
    $item = [
      '#type' => 'container',
      '#attributes' => ['class' => ['person-item', 'd-flex', $disabled ? 'purchaser-participation-item' : '']],
    ];

    $item['title'] = [
      '#type' => 'markup',
      '#markup' => '<h3>' . $this->t('@number', ['@number' => ($i + 1)]) . '</h3>',
      '#prefix' => '<div class="person-item-title sub-heading">',
      '#suffix' => '</div>',
    ];

    $item['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $first_name,
      '#attributes' => [
        'readonly' => $disabled ? 'readonly' : FALSE
      ]
    ];

    $item['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $last_name,
      '#attributes' => [
        'readonly' => $disabled ? 'readonly' : FALSE
      ]
    ];
    $item['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $email,
      '#attributes' => [
        'readonly' => $disabledEmail ? 'readonly' : ($disabled ? 'readonly' : FALSE)
      ]
    ];
    $item['remove_button'] = [
      '#type' => 'submit',
      '#value' => '',
      '#name' => 'remove_person_' . $i,
      '#attributes' => ['class' => ['remove-person-item', 'button-secondary']],
      '#prefix' => '<div class="remove-button--wrapper">',
      '#suffix' => '</div>',
      '#disabled' => $disabled
    ];
    $item['role'] = [
      '#type' => 'hidden',
      '#default_value' => $role,
    ];
    $item['expansion'] = [
      '#type' => 'hidden',
      '#default_value' => $disabled ? 0 : 1,
    ];
    return $item;
  }

  /**
   * Get the pane title.
   */
  public function getTitle() {
    return $this->order->bundle() === 'default' ? 'Participants' : 'Additional Participants';
  
  }

  /**
   * Get the pane subtitle.
   */
  public function getSubtitle($group) {
    return $group === NULL ? 'Add Participants' : 'Add Participants to ' . $group->label();
  }

  /**
   * Get the pane subtitle.
   */
  public function getDescription() {
    $default_description = 'Add a participant for every individual who will take the GDMI, this should include the purchaser/administrator if they expect to take the assessment. Each instance will constitute one GDMI assessment purchased.';
    $group_description = 'Add a Participant for every individual who will take the GDMI, this should include the purchaser/admin if they expect to take the assessment. Each instance will constitute one GDMI assessment purchased.';
    return $this->order->bundle() === 'default' ? $default_description : $group_description;
  }

  public function getDesignatedAdminDisabled($email) {
    $designated_admin = \Drupal::service('tb_gdmi.gdmi_utils')->getExistingUser($email);
    $first_name = $designated_admin !== NULL ? $designated_admin->field_first_name->value : '';
    $last_name = $designated_admin !== NULL ? $designated_admin->field_last_name->value : '';
    $designated_admin_disabled = $designated_admin !== NULL;
    $designated_admin_disabled = $designated_admin_disabled && ($first_name === NULL || $last_name === NULL) ? FALSE : $designated_admin_disabled;
    return [
      'disabled' => $designated_admin_disabled,
      'first_name' => $first_name,
      'last_name' => $last_name
    ];
  }
}
