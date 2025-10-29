<?php
namespace Drupal\tb_gdmi\Controller;

use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DateHelper;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\tb_gdmi\Element\DatelistSingleTime;
use Drupal\tb_gdmi\Services\GdmiGroups;
use Drupal\tb_gdmi\Services\GdmiMailer;
use Drupal\tb_gdmi\Services\GdmiUtils;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Provides route response for the gdmi manage groups.
 */
class ManageGroupsController extends ControllerBase {
  
  /**
   * The group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * The gdmi mail service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiMailer
   */
  protected $gdmiMailer;

  /**
   * The gdmi groups service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiGroups
   */
  protected $gdmiGroups;

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * Constructs a new DashboardController object.
   *
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   * @param \Drupal\tb_gdmi\Services\GdmiMailer $gdmi_mailer
   *   The GDMI mailer service.
   * @param \Drupal\tb_gdmi\Services\GdmiGroups $gdmi_groups
   *   The GDMI groups service.
   */
  public function __construct(GroupMembershipLoaderInterface $membership_loader, BlockManagerInterface $block_manager, 
    GdmiUtils $gdmi_utils, GdmiMailer $gdmi_mailer, GdmiGroups $gdmi_groups) {
    $this->membershipLoader = $membership_loader;
    $this->blockManager = $block_manager;
    $this->gdmiUtils = $gdmi_utils;
    $this->gdmiMailer = $gdmi_mailer;
    $this->gdmiGroups = $gdmi_groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('group.membership_loader'),
      $container->get('plugin.manager.block'),
      $container->get('tb_gdmi.gdmi_utils'),
      $container->get('tb_gdmi.gdmi_mailer'),
      $container->get('tb_gdmi.gdmi_groups'),
    );
  }

  /**
   * Returns a dashboard groups page.
   *
   * @return array
   *   A renderable array.
   */
  public function manageGroupsPage() {
    
    $build['available_groups'] = $this->blockManager->createInstance('gdmi_user_available_groups')
      ->build();

    return [
      '#theme' => 'dashboard_groups_page',
      '#children' => $build
    ];
  }

   /**
   * Show the group schedule form.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function groupSchedulePage(GroupInterface $group, Request $request) {
    $form = $this->entityFormBuilder()->getForm($group, 'group_scheduling');
    
    $form['actions']['submit']['#value'] = $this->t('COMMUNICATIONS');
    $form['actions']['submit']['#tag'] = 'button';
    $form['actions']['submit']['#icon'] = [ 'class' => 'fa-arrow-right', 'position' => 'right'];

    $destination = $request->query->get('destination');
    $local_task = $request->query->get('local_task');
    $is_editing = $destination !== NULL || $local_task !== NULL;

    $back_url = Url::fromRoute('tb_gdmi.dashboard_groups');
    if ($is_editing) {
      if ($destination !== NULL) {
        $back_url = Url::fromUserInput($destination);
      }
      $form['actions']['submit']['#value'] = $this->t('SAVE CHANGES');
      $form['actions']['submit']['#attributes']['class'][] = 'ml-auto mr-auto d-block btn-edit-save';
      unset($form['actions']['submit']['#icon']);
      $submit_btn = $form['actions']['submit'];
      unset($form['actions']['submit']);
      $form['submit_btn'] = $submit_btn;
    }

    $form['actions']['back'] = [
      '#type' => 'link',
      '#title' => Markup::create('<i class="fas fa-arrow-left mr-2" aria-hidden="true"></i> BACK'),
      '#url' => $back_url,
      '#attributes' => [
        'class' => ['btn', 'btn-gold']
      ]
    ];

    return $form;
  }

  /**
   * Show the group communications page "Add admins".
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function groupCommunicationsPage(GroupInterface $group, Request $request) {
    $owner = $group->getOwner();
    $admin_members = $this->membershipLoader->loadByGroup($group, 'gdmi-admin');
    $message_config = \Drupal::config('tb_gdmi.groups_communications')->get();
    $participants_default_message = \Drupal::config('tb_gdmi.groups_participant_invitation_settings')->get();
    $reminders_default_message = \Drupal::config('tb_gdmi.groups_participant_reminders_settings')->get();

    $date_helper = new DateHelper();
    $options = [];
    $options['date']['day'] = $date_helper->days(FALSE);
    $options['date']['month'] = $date_helper->monthNames(FALSE);
    $options['date']['year'] = $date_helper->years(2025, 2050, FALSE);
    $options['date']['time'] = DatelistSingleTime::generateTimeOptions(30);

    $defaults = [];

    $schedule_type = $group->field_partic_schedule_type->value;
    $schedule_type = $schedule_type === NULL ? 'immediately' : $schedule_type;
    $defaults['schedule_type'] = $schedule_type;
    
    $message_type = $group->field_partic_invit_msg_type->value;
    $message_type = $message_type === NULL ? 'default' : $message_type;
    $defaults['message_type'] = $message_type;
    
    $reminders_type = $group->field_partic_reminders_type->value;
    $reminders_type = $reminders_type === NULL ? 'default' : $reminders_type;
    $defaults['reminders_type'] = $reminders_type;
    
    if ($message_type === 'custom') {
      $defaults['partic_message_title'] = $group->field_partic_custom_msg_title->value;
      $defaults['partic_message_body'] = $group->field_partic_custom_msg_body->value;
    }
    
    if ($schedule_type === 'datetime') {
      $date = $group->get('field_partic_inv_schedule_date')->date;
      $defaults['partic_schedule']['day'] = $date->format('j');
      $defaults['partic_schedule']['month'] = $date->format('n');
      $defaults['partic_schedule']['year'] = $date->format('Y');
      $defaults['partic_schedule']['time'] = $date->format('g:i A');
    }

    $reminders = $group->field_custom_reminders->referencedEntities();
    $reminders_build = [];
    for ($i=0; $i < count($reminders); $i++) {
      $paragraph = $reminders[$i];

      $date_parts = [];
      if ($paragraph->field_schedule_type->value === 'datetime') {
        $date = $paragraph->get('field_date')->date;
        $date_parts['day'] = $date->format('j');
        $date_parts['month'] = $date->format('n');
        $date_parts['year'] = $date->format('Y');
        $date_parts['time'] = $date->format('g:i A');
      }

      $reminders_build[] = [
        '#theme' => 'participant_reminder_form_template',
        '#index' => ($i + 1),
        '#collapsed' => TRUE,
        '#pid' => $paragraph->id(),
        '#edit_enabled' => TRUE,
        '#date_options' => $options['date'],
        '#date_parts' => $date_parts,
        '#default_message' => $reminders_default_message,
        '#label' => $this->gdmiUtils->reminderLabel($paragraph),
        '#schedule_type' => $paragraph->field_schedule_type->value,
        '#days' => $paragraph->field_days->value,
        '#message_type' => $paragraph->field_message_type->value,
        '#message_title' => $paragraph->field_message_title->value,
        '#message_body' => $paragraph->field_message_body->value,
      ];
    }

    $form_action_url = Url::fromRoute('tb_gdmi.dashboard_groups_add_admins',
      ['group' => $group->id()],
      ['query' => $this->gdmiUtils->getCommunicationsRedirectParams($request)])->toString();
    
    $lb_data = $this->gdmiUtils->getCommunicationsLabelsAndBackUrl($group, $request);

    return [
      '#theme' => 'dashboard_groups_communications',
      '#content' => [
        'title' => $lb_data['title'],
        'owner' => $owner,
        'admin_members' => $admin_members,
        'group' => $group,
        'back_url' => $lb_data['back_url'],
        'summit_text' => $lb_data['summit_text'],
        'form_action' => $form_action_url,
        'options' => $options,
        'participants_default_message' => $participants_default_message,
        'defaults' => $defaults,
        'reminders' => $reminders_build,
        'request_action' => $request->query->get('action') ?? 'all'
      ],
      '#attached' => [
        'library' => [
          'tb_gdmi/group_add_admins',
          'tb_gdmi/group_participants_invitation',
          'tb_gdmi/group_participants_reminders',
        ],
        'drupalSettings' => [
          'tb_gdmi' => [
            'messageCommunication' => $message_config 
          ]
        ]
      ]
    ];
  }

  /**
   * Remove admin of a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function groupRemoveAdmin(GroupInterface $group, Request $request) {
    $data = $request->request->all();
    $response = ['status' => JsonResponse::HTTP_OK];

    if (isset($data['uid']) && !empty($data['uid'])) {
      /** @var \Drupal\user\UserInterface $user */
      $user = $this->entityTypeManager()->getStorage('user')->load($data['uid']);
      if ($user !== NULL) {
        if ($group->getOwnerId() !== $user->id()) {
       
          $group_purchaser = $this->gdmiGroups->getGroupPurchaser($group);

          if ($group_purchaser !== NULL) {
            if ($group_purchaser->id() !== $user->id()) {

              $membership = $group->getMember($user);
              $group_content = $membership->getGroupContent();
              $roles = array_column($group_content->group_roles->getValue(), 'target_id');
              $role_admin = array_search('gdmi-admin', $roles);
              if ($role_admin !== FALSE) {
                unset($roles[$role_admin]);
                $group_content->group_roles->setValue($roles);
                $group_content->save();
              } else {
                $group->removeMember($user);
              }
              
              $user_memberships = $this->membershipLoader->loadByUser($user);
              if (empty($user_memberships) && $user->hasRole('gdmi_participant') && !$user->hasRole('gdmi_purchaser')) {
                $this->entityTypeManager()->getStorage('user')->delete([$user]);
              }

            } else {
              $response['status'] = JsonResponse::HTTP_FORBIDDEN;
              $response['message'] = $this->t('You can\'t delete the original purchaser.');
            }
          } 
        } else {
          $response['status'] = JsonResponse::HTTP_FORBIDDEN;
          $response['message'] = $this->t('You can\'t delete the group primary admin original.');
        }
      }
    }

    return new JsonResponse($response);
  }

  /**
   * Add admin to a group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function groupAddAdmin(GroupInterface $group, Request $request) {
    $data = $request->request->all();
    $response = [];

    if ($request->request->getBoolean('isNew')) {
      if (isset($data['email']) && !empty($data['email'])) {

        /** @var \Drupal\user\UserInterface $user */
        $user = $this->gdmiUtils->getExistingUser($data['email']);

        if ($user !== NULL) {
          // Check membership.
          $membership = $group->getMember($user);
          if ($membership) {
            $group_content = $membership->getGroupContent();
            $roles = array_column($group_content->group_roles->getValue(), 'target_id');
            $group_content->group_roles->setValue(array_merge($roles, ['gdmi-admin']));
            $group_content->save();
          } else {
            $group->addMember($user, ['group_roles' => 'gdmi-admin']);
          }

          if (!$user->hasRole('gdmi_purchaser')) {
            $user->addRole('gdmi_purchaser');
            $user->save();
          }

        } else {
          $user = User::create();
          $user->setEmail($data['email']);
          $user->setUsername($data['email']);
          $user->setPassword($this->gdmiUtils->generateUserPassword());
          $user->set('field_domain_access', ['gdmi']);
          $user->addRole('gdmi_purchaser');
          $user->set('status', FALSE);
          $user->save();
          $group->addMember($user, ['group_roles' => 'gdmi-admin']);
        }

        $response['uid'] = $user->id();

        $this->gdmiMailer->notifyUserAdmin($user, $group);
        
      }
    } else {
       /** @var \Drupal\user\UserInterface $user */
       $user = $this->entityTypeManager()->getStorage('user')->load($data['uid']);
       /** @var \Drupal\user\UserInterface[] $user_email */
       $user_email = $this->entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $data['email']]);

       if (empty($user_email) && $user->getEmail() !== $data['email']) {
         $user->setEmail($data['email']);
         $user->setUsername($data['email']);
         if (!$user->hasRole('gdmi_purchaser')) {
          $user->addRole('gdmi_purchaser');
         }
         $user->save();
       }

       if (!empty($user_email)) {
        $response['message'] = $this->t('User @mail already exist so you can\'t set that email to this user', ['@mail' => $data['email']]);
       }

       $response['uid'] = $user->id();
    }

    if (isset($data['primary_admin']) && !empty($data['primary_admin']) && $group->getOwnerId() !== $data['primary_admin']) {
      $old_owner = $group->getOwner();
      /** @var \Drupal\user\UserInterface[] $new_owner */
      $new_owner = $this->entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $data['primary_admin']]);
      $new_owner = reset($new_owner);
      $this->gdmiGroups->updateGroupPrimaryAdmin($group, $new_owner, $old_owner);
    }

    $group->save();

    return new JsonResponse(['status' => JsonResponse::HTTP_OK, 'response' => $response]);
  }

  /**
   * Update the group admins.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function groupAddAdmins(GroupInterface $group, Request $request) {
    $data = $request->request->all();
    $request_action = $request->query->get('action') ?? 'all';

    $redirect_url = Url::fromRoute('tb_gdmi.dashboard_groups_communications',
      ['group' => $group->id()],
      ['query' => $this->gdmiUtils->getCommunicationsRedirectParams($request)]);

    // Validations.
    if ($request_action === 'all' || $request_action === 'communications') {
      $part_schedule_date = NULL;
      if ($data['participants_schedule_type'] === 'datetime') {
        $date_result = $this->gdmiUtils->generateDateValue($data['participant_inivitation_day'], 
        $data['participant_inivitation_month'], $data['participant_inivitation_year'], $data['participant_inivitation_time'], $group->field_time_zone->value);
        if (!empty($date_result)) {
          if (!isset($date_result['error'])) {
            $part_schedule_date = $date_result['date'];
          } else {
            $this->messenger()->addError('Selected combination of day and month is not valid.');
            return new RedirectResponse($redirect_url->toString());
          }
        } else {
          $this->messenger()->addError('To schedule the invitation of participants on a certain date at a certain time, it is necessary to set the fields day, month, year and time.');
          return new RedirectResponse($redirect_url->toString());
        }
      }
  
      if ($data['participants_message_type'] === 'custom') {
        if (empty($data['custom_message_title'])) {
          $this->messenger()->addError('If you select a participant custom invitation message the message title is required.');
          return new RedirectResponse($redirect_url->toString());
        }
  
        if (empty($data['custom_message_body'])) {
          $this->messenger()->addError('If you select a participant custom invitation message the message body is required');
          return new RedirectResponse($redirect_url->toString());
        }
      }

      // Participants invitations settings.
      $group->field_partic_schedule_type->value = $data['participants_schedule_type'];
      $group->field_partic_reminders_type->value = $data['participants_reminders_type'];
      $group->field_partic_invit_msg_type->value = $data['participants_message_type'];

      if ($data['participants_schedule_type'] === 'datetime' && $part_schedule_date !== NULL) {
        $fdate =  $part_schedule_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
        $group->set('field_partic_inv_schedule_date', $fdate);
      }

      if ($data['participants_message_type'] === 'custom') {
        $group->set('field_partic_custom_msg_title', $data['custom_message_title']);
        $group->set('field_partic_custom_msg_body', $data['custom_message_body']);
      }
    }

    // Update primary admin.
    if ($request_action === 'all' || $request_action === 'admins') {
      if ($group->getOwnerId() !== $data['primary_admin']) {
        $old_owner = $group->getOwner();
        /** @var \Drupal\user\UserInterface $new_owner */
        $new_owner = $this->entityTypeManager()->getStorage('user')->load($data['primary_admin']);
        $this->gdmiGroups->updateGroupPrimaryAdmin($group, $new_owner, $old_owner);
      }
    }

    $group->save();

    
    if ($request_action !== 'all') {
      $this->messenger()->addStatus( ucfirst($request_action) . ' saved successfully.');
      $redirect_url = Url::fromRoute('tb_gdmi.dashboard_groups_communications', ['group' => $group->id(), 'local_task' => 1, 'action' => $request_action]);
    } else {
      $this->messenger()->addStatus('Communications saved successfully.');
      $redirect_url = Url::fromRoute('tb_gdmi.dashboard_groups_summary', ['group' => $group->id()]);
    }

    return new RedirectResponse($redirect_url->toString());
  }

  /**
   * Show the group summary page.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to launch.
   */
  public function groupSummaryPage(GroupInterface $group) {

    $members = $group->getMembers(['gdmi-participant']);
    $admins = $group->getMembers(['gdmi-primary_admin', 'gdmi-admin']);

    // Get the gdmi version.
    $access_codes = reset($members)->getUser()->field_gdmi_assessment_code;
    $group_access_code = $this->gdmiUtils->getUserAccessCodeByGroup($access_codes, $group->id());
    $product = $this->gdmiUtils->getProductByWebformId($group_access_code->webform_id);
    $gdmi_version = reset($product)->getTitle();

    $communications_data = $this->gdmiUtils->groupCommunicationsData($group);
    
    return [
      '#theme' => 'dashboard_groups_summary',
      '#content' =>  [
        'group' => $group, 
        'members' => $members,
        'admins' => $admins,
        'gdmi_version' => $gdmi_version,
        'timezone_formatted' => $this->gdmiUtils->formatTimezone($group->field_time_zone->value),
        'participants_invite' => $communications_data['participants_invite'],
        'participants_reminders' => $communications_data['participants_reminders']
      ]
    ];
  }

  /**
   * Launch a specific group.
   *
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group to launch.
   */
  public function launchGroup(GroupInterface $group) {
    $group->set('field_status', 'ongoing');

    // Send immediately invitation.
    $schedule_type = $group->field_partic_schedule_type->value;
    if ($schedule_type == 'immediately') {
     $participants = $this->membershipLoader->loadByGroup($group, ['gdmi-participant']);
     $this->gdmiMailer->sendParticipantsInvitations($group, $participants);
     $group->set('field_invitations_sent', TRUE);
     $current_date = new DrupalDateTime('now');
     $group->set('field_invite_sent_date', $current_date->format('Y-m-d\TH:i:s'));
    }
    
    $group->save();

    $this->messenger()->addStatus('Group launched successfully.');

    return new RedirectResponse(Url::fromRoute('tb_gdmi.dashboard_groups')->toString());
  }

  /**
   * Resend invitation email to a user.
   * 
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group of the invitation.
   * @param \Drupal\user\UserInterface $user
   *   The user to send the invitation.
   */
  public function resendInviteEmail(GroupInterface $group, UserInterface $user) {
    $response = $this->gdmiMailer->notifyUser($user, $group);
    if (isset($response['sendResult']) && $response['sendResult'] == 'SENT') {
      $this->messenger()->addStatus('User invitation email sent successfully.');
    }
    return new RedirectResponse(Url::fromRoute('tb_gdmi.dashboard_groups')->toString());
  }

  /**
   * Send reset password email to a user.
   * 
   * @param \Drupal\user\UserInterface $user
   *   The user to send the invitation.
   */
  public function sendResetPasswordEmail(UserInterface $user) {
    $response = $this->gdmiMailer->resetPassword($user);
    if (isset($response['sendResult']) && $response['sendResult'] == 'SENT') {
      $this->messenger()->addStatus('User reset password email sent successfully.');
    }
    return new RedirectResponse(Url::fromRoute('tb_gdmi.dashboard_groups')->toString());
  }

  /**
   * Group participant reminder template form.
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function participantReminderFormTemplate(Request $request) {
    $data = $request->request->all();

    $date_helper = new DateHelper();
    $date_options = [];
    $date_options['day'] = $date_helper->days(FALSE);
    $date_options['month'] = $date_helper->monthNames(FALSE);
    $date_options['year'] = $date_helper->years(2025, 2050, FALSE);
    $date_options['time'] = DatelistSingleTime::generateTimeOptions(30);

    $default_message = \Drupal::config('tb_gdmi.groups_participant_reminders_settings')->get();

    $build = [
      '#theme' => 'participant_reminder_form_template',
      '#date_options' => $date_options,
      '#default_message' => $default_message,
      '#index' => $data['index'],
    ];
    $rendered_output = \Drupal::service('renderer')->render($build);
    return new Response($rendered_output);
  }

  /**
   * Add or update a group participant reminder.
   * 
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group of the reminder.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function addParticipantReminder(GroupInterface $group, Request $request) {
    $data = $request->request->all();
    $response = [];

    $schedule_type = $data['schedule_type'];
    $days_amount = $data['days'];
    $message_type = $data['message_type'];
    $message_title = $data['message_title'];
    $message_body = $data['message_body'];

    $reminder_schedule_date = NULL;
    if ($schedule_type === 'datetime') {
      $date_result = $this->gdmiUtils->generateDateValue($data['day'], $data['month'], $data['year'], $data['time'], $group->field_time_zone->value);
      if (!empty($date_result)) {
        if (!isset($date_result['error'])) {
          $reminder_schedule_date = $date_result['date'];
          $reminder_schedule_date = $reminder_schedule_date->format(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);
        } else {
          $response['status'] = Response::HTTP_BAD_REQUEST;
          $response['field_error'] = 'date';
          $response['message'] = 'Selected combination of day and month is not valid.';
        }
      } else {
        $response['status'] = Response::HTTP_BAD_REQUEST;
        $response['field_error'] = 'date';
        $response['message'] = 'To schedule the invitation of participants on a certain date at a certain time, it is necessary to set the fields day, month, year and time.';
      }
    }
    
    if ($message_type === 'custom') {
      if (empty($message_title)) {
        $response['status'] = Response::HTTP_BAD_REQUEST;
        $response['field_error'] = 'message_title';
        $response['message'] = 'If you select a participant custom invitation message the message title is required.';
      }
      
      if (empty($message_body)) {
        $response['status'] = Response::HTTP_BAD_REQUEST;
        $response['field_error'] = 'message_body';
        $response['message'] = 'If you select a participant custom invitation message the message body is required.';
      }
    }

    // If there are error send the response.
    if (isset($response['status'])) {
      return new JsonResponse($response);
    }

    if ($request->request->getBoolean('isNew')) {

      $paragraph = Paragraph::create([
        'type' => 'gdmi_group_participant_reminder',
        'field_schedule_type' => $schedule_type,
        'field_date' =>  $reminder_schedule_date,
        'field_days' => $days_amount,
        'field_message_type' => $message_type,
        'field_message_title' => $message_title,
        'field_message_body' => $message_body
      ]);

      $paragraph->save();

      $group->get('field_custom_reminders')->appendItem([
        'target_id' => $paragraph->id(),
        'target_revision_id' => $paragraph->getRevisionId(),
      ]);
      
      $group->save();

    } else {

      $paragraph = Paragraph::load($data['pid']);
      $paragraph->field_schedule_type->value = $schedule_type;
      $paragraph->set('field_date', $reminder_schedule_date);
      $paragraph->field_days->value = $days_amount;
      $paragraph->field_message_type->value = $message_type;
      $paragraph->field_message_title->value = $message_title;
      $paragraph->field_message_body->value = $message_body;
      $paragraph->save();

    }

    $response['status'] = Response::HTTP_OK;
    $response['pid'] = $paragraph->id();
    $response['label'] = $this->gdmiUtils->reminderLabel($paragraph);

    return new JsonResponse($response);
  }

  /**
   * Remove group participant reminder.
   * 
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group of the reminder.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function removeParticipantReminder(GroupInterface $group, Request $request) {
    $data = $request->request->all();
    $response = [];

    if (isset($data['pid']) && !empty($data['pid'])) {
      $paragraph_entity = Paragraph::load($data['pid']);
      $paragraphsField = $group->get('field_custom_reminders')->getValue();

      // Iterate through the paragraphs and remove the target paragraph.
      foreach ($paragraphsField as $key => $paragraph) {
        if ($paragraph['target_id'] == $data['pid']) {
          unset($paragraphsField[$key]);
          break;
        }
      }

      // Set the updated values back to the Paragraphs field.
      $group->set('field_custom_reminders', $paragraphsField);
      $group->save();

      $paragraph_entity->delete();
      $response['status'] = Response::HTTP_OK;
    }

    return new JsonResponse($response);
  }

  /**
   * Add participants checkout redirect.
   * 
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group of the reminder.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function addParticipants(GroupInterface $group, Request $request) {

    $cartProvider = \Drupal::service('commerce_cart.cart_provider');
    $cartManager = \Drupal::service('commerce_cart.cart_manager');
    $cart = $cartProvider->getCart('gdmi_expand_participants');

    // Define the needed variation type.
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $assessment_orders */
    $assessment_orders = $this->entityTypeManager()->getStorage('commerce_order')->loadByProperties([
      'state' => 'completed',
      'field_group' => $group->id(),
      'type' => 'default'
    ]);

    if (!empty($assessment_orders)) {
    
      $assessment_order = reset($assessment_orders);
      $assessment_product_items = $assessment_order->getItems();
      $assessment_product_item = reset($assessment_product_items);
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
      $purchased_entity = $assessment_product_item->getPurchasedEntity();
      $product = $purchased_entity->getProduct();
      $variation_obj = $product->field_participants_expansion->entity;
      
      if (!$cart) {
        $stores = $variation_obj->getStores();
        $store = reset($stores);
        $cart = $cartProvider->createCart('gdmi_expand_participants', $store);
      }

      $cart->setData('group_id', $group->id());
      $cartManager->addEntity($cart, $variation_obj);
      
      $redirect_url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id()]);
    
    } else {
      
      $this->messenger()->addError('There is not a order related to the group.');
      $redirect_url = Url::fromRoute('tb_gdmi.dashboard');
    
    }
    
    return  new RedirectResponse($redirect_url->toString());
  }

  /**
   * Redirection to create new group of the default type.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function createGroup(Request $request) {

    $cartProvider = \Drupal::service('commerce_cart.cart_provider');
    $cartManager = \Drupal::service('commerce_cart.cart_manager');
    $cart = $cartProvider->getCart('default');

    $config = $this->config('tb_gdmi.purchasing_page_settings');
    $product_id = $config->get('product_items')[0];

    /** @var \Drupal\commerce_order\Entity\Product $product */
    $product = $this->entityTypeManager()->getStorage('commerce_product')->load($product_id);
    $product_variation_id = $product->get('variations')->getValue()[0]['target_id'];
    /** @var \Drupal\commerce_order\Entity\ProductVariation $variationobj */
    $variationobj = $this->entityTypeManager()->getStorage('commerce_product_variation')->load($product_variation_id);

    if (!$cart) {
      $stores = $variationobj->getStores();
      $store = reset($stores);
      $cart = $cartProvider->createCart('default', $store);
    }
    
    $cartManager->addEntity($cart, $variationobj);
    
    $checkout_url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id()]);
    return  new RedirectResponse($checkout_url ->toString());
  }

}