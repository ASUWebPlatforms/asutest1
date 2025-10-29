<?php

namespace Drupal\tb_gdmi\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\commerce_checkout\Event\CheckoutEvents;
use Drupal\webform_invitation\InvitationCodes;
use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\group\Entity\Group;
use Drupal\tb_gdmi\Services\GdmiMailer;
use Drupal\tb_gdmi\Services\GdmiUtils;
use Drupal\user\Entity\User;

/**
 * Defines the event subscriber for checkout completion.
 */
class CheckoutCompletionSubscriber implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The invitation codes service.
   *
   * @var \Drupal\webform_invitation\InvitationCodes
   */
  protected $invitationCodes;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The gdmi mail service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiMailer
   */
  protected $gdmiMailer;
  
  /**
   * The gdmi utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * Constructs a new CheckoutCompletionSubscriber instance.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\webform_invitation\InvitationCodes $invitation_codes
   *   The invitation codes service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\tb_gdmi\Services\GdmiMailer $gdmi_mailer
   *   The gdmi mailer service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The gdmi utils service.
   */
  public function __construct(Connection $connection, InvitationCodes $invitation_codes, MessengerInterface $messenger, GdmiMailer $gdmi_mailer, GdmiUtils $gdmi_utils) {
    $this->connection = $connection;
    $this->invitationCodes = $invitation_codes;
    $this->messenger = $messenger;
    $this->gdmiMailer = $gdmi_mailer;
    $this->gdmiUtils = $gdmi_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[CheckoutEvents::COMPLETION][] = ['onCheckoutComplete'];
    $events[CheckoutEvents::COMPLETION_REGISTER][] = ['onCheckoutComplete'];
    return $events;
  }

  /**
   * Performs custom actions when the checkout is completed.
   */
  public function onCheckoutComplete($event) {

    $order = $event->getOrder();
    $customer = $order->getCustomer();

    if ($customer->id() === '0') {
      return;
    }

    if ($order->bundle() === 'default') {
      foreach ($order->getItems() as $order_item) {
        /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
        $purchased_entity = $order_item->getPurchasedEntity();
        $bundle = $purchased_entity->bundle();
        if ($bundle === 'gdmi_assessment') {
          $product = $purchased_entity->getProduct();
          $webform = $product->field_assessment_webform->entity;
          $type = strtolower($purchased_entity->attribute_assessment_type->entity->label());
          
          if ($type === 'individual') {
            $code = $this->addAccessCodeToUser($customer, $webform);
            $order->set('field_individual_code', $code);
            $order->save();
          } elseif ($type === 'group') {

            $group_name = $order->getData('group_name');
            $organization = $order->getData('organization');
            $participants = $order->getData('participants');
            $purchaser_participation = $order->getData('purchaser_participation');
  
            $newGroup = Group::create([
              'type' => 'gdmi',
              'label' => $group_name,
              'uid' => $customer->id(),
              'field_organization' => $organization,
            ]);
            $newGroup->save();

            $designated_admin = $this->addUsersToGroup($newGroup, $participants, $order, $purchaser_participation);

            if ($order->getData('admin_designation') === 'other') {
              $membership = $newGroup->getMember($newGroup->getOwner());
              $group_content = $membership->getGroupContent();
              $roles = array_column($group_content->group_roles->getValue(), 'target_id');
              $role_admin = array_search('gdmi-primary_admin', $roles);
              if ($role_admin !== FALSE) {
                unset($roles[$role_admin]);
                $group_content->group_roles->setValue($roles);
                $group_content->save();
              }

              /** @var \Drupal\user\Entity\User $designated_admin */
              $designated_admin = $designated_admin === NULL ?  $this->adminDesignation($order) : $designated_admin;
              if ($designated_admin !== NULL) {
                $newGroup->setOwner($designated_admin);
                $admin_designation_participation = $order->getData('admin_designation_participation');
                $roles = $admin_designation_participation === '1' ? ['gdmi-primary_admin', 'gdmi-participant'] : ['gdmi-primary_admin'];
                $newGroup->addMember($designated_admin, ['group_roles' => $roles]);
                $newGroup->save();

                if (!$designated_admin->hasRole('gdmi_purchaser')) {
                  $designated_admin->addRole('gdmi_purchaser');
                  $designated_admin->save();
                }

                // Send email only when designated admin is other person.
                if ($customer->id() !== $designated_admin->id()) {
                  $this->gdmiMailer->notifyUserAdmin($designated_admin, $newGroup);
                }
              }
            }

   
            $this->groupMembersAccessCode($webform, $newGroup->getMembers(), $newGroup->id());
  
            $order->set('field_group', $newGroup);
            $order->save();
          }
        }
      }

      $current_user = User::load(\Drupal::currentUser()->id());
      if (!$current_user->hasRole('gdmi_purchaser')) {
        $current_user->addRole('gdmi_purchaser');
        $current_user->save();
      }
    }

    if ($order->bundle() === 'gdmi_expand_participants') {
      
      $group_id = $order->getData('group_id');
      $participants = $order->getData('participants');

      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = \Drupal::entityTypeManager()->getStorage('group')->load($group_id);

      /** @var \Drupal\commerce_order\Entity\OrderInterface[] $assessment_orders */
      $assessment_orders = \Drupal::entityTypeManager()->getStorage('commerce_order')->loadByProperties([
        'state' => 'completed',
        'field_group' => $group->id(),
        'type' => 'default'
      ]);

      $assessment_order = reset($assessment_orders);
      $assessment_product_items = $assessment_order->getItems();
      $assessment_product_item = reset($assessment_product_items);

      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $purchased_entity */
      $purchased_entity = $assessment_product_item->getPurchasedEntity();
      $product = $purchased_entity->getProduct();
      $webform = $product->field_assessment_webform->entity;

      $this->addUsersToGroup($group, $participants, $order, FALSE);
      $this->groupMembersAccessCode($webform, $group->getMembers(), $group->id(), TRUE);

      $order->set('field_group', $group);
      $order->save();
    }


    $this->gdmiMailer->orderReceipt($customer, $order);
  }

  /**
   * Add existing or create new users and add them to the group.
   * 
   * @param Group $group
   *  Group where to insert the users.
   * 
   * @param array $participants
   *  Paragraphs containing the required data to create or add Users.
   * 
   * @param object $order
   *  The current order object.
   * 
   * @param bool $purchaser_participation
   *  Defines if the purchaser will be a participant.
   * 
   */
  protected function addUsersToGroup(Group &$group, array $participants, $order, $purchaser_participation = FALSE) {

    $admin_designation = $order->getData('admin_designation');
    $admin_designation_participation = $order->getData('admin_designation_participation');
    $admin_designation_email = $order->getData('admin_designation_email');
    $designated_admin = NULL;

    foreach ($participants as $index => $participant) {

      $email = $participant['email'];
      $first_name = $participant['first_name'];
      $last_name = $participant['last_name'];
      $is_designated_admin = $admin_designation === 'other' && $admin_designation_participation === '1' &&  $email === $admin_designation_email;
      
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->gdmiUtils->getExistingUser($email);
      $user = $account === NULL ? $this->createUser($email, $first_name, $last_name) : $this->updateUser($account, $first_name, $last_name);
      if ($is_designated_admin) {
        $designated_admin = $user;
        continue;
      }
      
      $membership = $group->getMember($user);

      if (!$membership) {
        $group->addMember($user, ['group_roles' => 'gdmi-participant']);
        // If the purchaser was added as participant update the group roles.
        if ((\Drupal::currentUser()->id() === $user->id()) && $purchaser_participation) {
          $owner_membership = $group->getMember($user);
          $group_content = $owner_membership->getGroupContent();
          $roles = array_column($group_content->group_roles->getValue(), 'target_id');
          if (!in_array('gdmi-participant', $roles)) {
            $group_content->group_roles->setValue(array_merge($roles, ['gdmi-participant']));
            $group_content->save();
          }
        }
      } else {
        $group_content = $membership->getGroupContent();
        $group_content->group_roles[] = 'gdmi-participant';
        $group_content->save();
      }

    }

    return  $designated_admin;
  }

  /**
   * Create a new Drupal user.
   *
   * @param string $username
   *   The username for the new user.
   * @param string $email
   *   The email address for the new user.
   * @param string $first_name
   *   The fist name of the new user.
   * @param string $last_name
   *   The last name of the new user.
   * @param array $roles
   *   The list of roles.
   *
   * @return \Drupal\user\Entity\User|false
   *   The created user entity if successful, or false if an error occurred.
   */
  protected function createUser(string $email, string $first_name, string $last_name, array $roles = ['gdmi_participant']) {
    // Generate a random password for the user.
    $password = $this->gdmiUtils->generateUserPassword();

    $current_user = User::load(\Drupal::currentUser()->id());
    $country = $current_user->get('field_country')[0]->value;

    // Create the user entity.
    $user = User::create();
    $user->setEmail($email);
    $user->setUsername($email);
    $user->setPassword($password);
    $user->set('field_first_name', $first_name);
    $user->set('field_last_name', $last_name);
    $user->set('field_country', $country);
    $user->set('field_domain_access', ['gdmi']);

    foreach ($roles as $role) {
      $user->addRole($role);
    }

    $user->set('status', FALSE);


    try {
      // Save the user entity.
      $user->save();
      return $user;
    } catch (\Exception $e) {
      // Handle any errors that occur during user creation.
      \Drupal::logger('tb_gdmi')->error('User creation failed: @message', ['@message' => $e->getMessage()]);
      return false;
    }
  }


  /**
   * Update an existent user.
   * 
   * @param \Drupal\user\Entity\User $user
   *  User to be updated.
   * @param string $first_name
   *   The new fist name of the user.
   * @param string $last_name
   *   The new last name of the user.
   * 
   * @return int|false
   *  The user ID or false if there is an error.
   * 
   */
  protected function updateUser(\Drupal\user\Entity\User $user, string $first_name, string $last_name) {
    $user->addRole('gdmi_participant');
    
    $old_first_name = $user->get('field_first_name')->getValue();
    $old_last_name = $user->get('field_last_name')->getValue();

    if (empty($old_first_name)) {
      $user->set('field_first_name', $first_name);
    }

    if (empty($old_last_name)) {
      $user->set('field_last_name', $last_name);
    }

    try {
      // Save the user entity.
      $user->save();
      return $user;
    } catch (\Exception $e) {
      // Handle any errors that occur during user creation.
      \Drupal::logger('tb_gdmi')->error('User update failed: @message', ['@message' => $e->getMessage()]);
      return false;
    }
  }

  /**
   * Adds the access code for every member of the group.
   *
   * @param \Drupal\webform\Entity\Webform $webform
   *  Webform where to insert the code.
   * @param array $members
   *  Set of members who will have access to the code.
   * @param int $group_id
   *  The group id.
   * @param bool $is_expand
   *  The is_expand value. 
   */
  protected function groupMembersAccessCode(\Drupal\webform\Entity\Webform $webform, array $members, int $group_id, $is_expand = FALSE) {
    foreach ($members as $participant) {
      $group_content = $participant->getGroupContent();
      $roles = array_column($group_content->group_roles->getValue(), 'target_id');
      if (in_array('gdmi-participant', $roles)) {
        $customer = $participant->getUser();
        if ($is_expand) {
          $code = $this->gdmiUtils->getUserAccessCodeByGroup($customer->field_gdmi_assessment_code, $group_id);
          if ($code === NULL) {
            $this->addAccessCodeToUser($customer, $webform, $group_id);
          }
        } else {
          $this->addAccessCodeToUser($customer, $webform, $group_id);
        }
      }
    }
  }

  /**
   * Adds the access code to an User.
   * 
   * @param \Drupal\user\Entity\User $user
   *  User to adds the code.
   * @param \Drupal\webform\Entity\Webform $webform
   *  Webform where to insert the code. 
   * @param int $group_id
   *  The group id. 
   */
  protected function addAccessCodeToUser(\Drupal\user\Entity\User $user, \Drupal\webform\Entity\Webform $webform, int $group_id = 0) {
    $this->invitationCodes->generate($webform->id(), 1);
  
    $result = $this->connection->select('webform_invitation_codes', 'wic')
      ->fields('wic', ['cid', 'code'])
      ->orderBy('cid', 'DESC')
      ->range(0, 1)
      ->execute();
    $last_insert = $result->fetchAssoc();

    $customer_codes = $user->get('field_gdmi_assessment_code');
    
    $customer_codes->appendItem([
      'webform_id' => $webform->id(),
      'access_code' => $last_insert['code'],
      'status' => FALSE,
      'group_id' => $group_id
    ]);

    $user->save();

    return $last_insert['code'];
  }

  private function adminDesignation($order, $roles = ['gdmi_participant', 'gdmi_purchaser']) {
    $admin_designation = $order->getData('admin_designation');
    $admin_designation_email = $order->getData('admin_designation_email');

    if ($admin_designation === 'other') {
      $existing_user = $this->gdmiUtils->getExistingUser($admin_designation_email);
      if ($existing_user === NULL) {
        $existing_user = $this->createUser($admin_designation_email, '', '', $roles);
      }
      return $existing_user;
    } else {
      $current_user = User::load(\Drupal::currentUser()->id());
      return $current_user;
    }
  }

}
