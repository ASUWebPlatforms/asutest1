<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\tb_gdmi\Services\GdmiGroups;
use Drupal\tb_gdmi\Services\GdmiUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GroupEditParticipantsForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * The gdmi mail service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiGroups
   */
  protected $gdmiGroups;

  /**
   * The group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;
  
  /**
   * {@inheritdoc}
  */
  public function getFormId() {
    return 'group_edit_participants_form';
  }

  /**
   * Constructs a new SubmissionSelectForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   * @param \Drupal\tb_gdmi\Services\GdmiGroups $gdmi_groups
   *   The GDMI groups service.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter, GdmiUtils $gdmi_utils,
    GdmiGroups $gdmi_groups, GroupMembershipLoaderInterface $membership_loader) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
    $this->gdmiUtils = $gdmi_utils;
    $this->gdmiGroups = $gdmi_groups;
    $this->membershipLoader = $membership_loader;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('tb_gdmi.gdmi_utils'),
      $container->get('tb_gdmi.gdmi_groups'),
      $container->get('group.membership_loader'),
    );
  }

  /**
   * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state, $group = NULL) {
    $form['#tree'] = TRUE;

    $form['title'] = [
      '#type' => 'markup',
      '#markup' => '<div class="container"><h1>Edit Participants</h1></div>',
    ];

    // // Group details.
    $form['group_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['gray-section'],
      ],
    ];

    // Participants.
    $form['group_wrapper']['participants_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['participants-wrapper', 'container'],
      ],
    ];

    $form['group_wrapper']['participants_wrapper']['subtitle'] = [
      '#type' => 'markup',
      '#markup' => '<h2 class="sub-heading">Edit Participants</h2>',
    ];

    $form['group_wrapper']['participants_wrapper']['description'] = [
      '#type' => 'markup',
      '#markup' => '<p class="description">You may edit existing participants information below. To <b>Add participants</b>, return to the Manage Groups page and select “+ ADD PARTICIPANTS”. To edit your own information, go to your <b>My Account</b> under the “Account” dropdown in the GDMI Hub bar above.</p>',
    ];
    
    $form['group_wrapper']['person_details'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['person-details-wrapper', 'container']],
      '#attached' => [
        'library' => ['tb_gdmi/edit_participants'],
      ],
    ];

    $group_purchaser = $this->gdmiGroups->getGroupPurchaser($group);
    $purchaser_membership = $group->getMember($group_purchaser);
    $purchaser_participant = FALSE;
    if ($purchaser_membership) {
      $roles = $purchaser_membership->getRoles();
      $purchaser_participant = isset($roles['gdmi-participant']);
    }

    $participants_members = $this->membershipLoader->loadByGroup($group, 'gdmi-participant');
    foreach ($participants_members as $index => $membership) {
      $member = $membership->getUser();
      $is_purchaser = $purchaser_participant && $purchaser_membership->getUser()->id() === $member->id();
      $membership_roles = $membership->getRoles();
      $disabled = $is_purchaser || isset($membership_roles['gdmi-admin']);
      $first_login = $member->getLastAccessedTime() !== '0';
      $form['group_wrapper']['person_details'][$index] = $this->addParticipantItem($index, $member->id(), $member->field_first_name->value, $member->field_last_name->value, $member->getEmail(), $disabled, $first_login);
    }

    $form['group_wrapper']['person_details']['submit'] = [
      '#type' => 'submit',
      '#attributes' => ['class' => ['d-block', 'mx-auto']],
      '#value' => $this->t('Save Changes'),
    ];

    $form['group_wrapper']['person_details']['btn_back'] = [
     '#type' => 'link',
      '#title' => Markup::create('<i class="fas fa-arrow-left mr-2" aria-hidden="true"></i> BACK'),
      '#url' => Url::fromRoute('tb_gdmi.dashboard_groups'),
      '#attributes' => [
        'class' => ['btn', 'btn-gold']
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $members =  $form_state->getUserInput()['group_wrapper']['person_details'];
    $user_storage = $this->entityTypeManager->getStorage('user');
    foreach ($members as $member) {
       /** @var \Drupal\user\UserInterface $user */
      $user = $user_storage->load($member['uid']);
      $user->set('field_first_name', $member['first_name']);
      $user->set('field_last_name', $member['last_name']);
      // TODO - Define the email update behavior.
      if (isset($member['email'])) {
        if ($user->getEmail() !== $member['email']) {
          $existing_user = $this->gdmiUtils->getExistingUser($member['email']);
          if ($existing_user === NULL) {
            $user->setEmail($member['email']);
            $user->setUsername($member['email']);
          } else {
            \Drupal::messenger()->addError('Email ' . $member['email'] . ' can\'t be used, it\'s already in use by another user.');
          }
        }
      }
      $user->save();
    }
    $group = $form_state->getBuildInfo()['args'][0];
    $form_state->setRedirect('tb_gdmi.group_edit_participants_form', ['group' => $group->id()]);
    \Drupal::messenger()->addMessage('Participants were successfully updated');
  }

  /**
   * Add participant item to the list.
   */
  public function addParticipantItem($i, $user_id = 0, $first_name = '', $last_name = '', $email = '', $disabled = FALSE, $disabledEmail = FALSE) {
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
    $item['uid'] = [
      '#type' => 'hidden',
      '#value' => $user_id,
      '#disabled' => $disabled,
    ];
    $item['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#default_value' => $first_name,
      '#disabled' => $disabled,
      '#required' => TRUE
    ];
    $item['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#default_value' => $last_name,
      '#disabled' => $disabled,
      '#required' => TRUE
    ];
    $item['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $email,
      '#disabled' => $disabled || $disabledEmail,
      '#required' => TRUE
    ];
    return $item;
  }
  
}
