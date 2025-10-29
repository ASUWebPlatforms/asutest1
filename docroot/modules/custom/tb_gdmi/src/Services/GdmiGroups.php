<?php

namespace Drupal\tb_gdmi\Services;

use DateTimeZone;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 *  GDMI groups service.
 */
class GdmiGroups {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The group membership loader service.
   *
   * @var \Drupal\group\GroupMembershipLoaderInterface
   */
  protected $membershipLoader;

  /**
   * Constructs the gdmi groups service.
   * 
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(GroupMembershipLoaderInterface $membership_loader, EntityTypeManagerInterface $entity_type_manager) {
    $this->membershipLoader = $membership_loader;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Get a list of the groups where a user is owner.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account entity.
   * @param string $group_type
   *   The groups types.
   */
  public function getGroupsWhereUserIsOwner(AccountInterface $account = NULL, $group_type = 'gdmi') {
    return $this->entityTypeManager->getStorage('group')->loadByProperties(['uid' => $account->id(), 'type' => $group_type]);
  }

  /**
   * Get a list of the available groups to a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account entity.
   * @param array $roles
   *   The user group roles.
   */
  public function getAvailableUserGroups(AccountInterface $account = NULL, array $roles = ['gdmi-admin']) {
    $groups = $this->getGroupsWhereUserIsOwner($account);
    $user_memberships = $this->membershipLoader->loadByUser($account, $roles);

    // @todo - refactor this code now we have the 'gdmi-primary-admin', 'gdmi-admin', 'gdmi-participant' so probably the $groups and related interation could be no needed.
    foreach ($user_memberships as $membership) {
      $add_group = TRUE;
      foreach ($groups as $group) {
        if($group->id() === $membership->getGroup()->id()) {
          $add_group = FALSE;
        }
      }
      if ($add_group) {
        $groups[] = $membership->getGroup();
      }
    }

    return $groups;
  }

  /**
   * Get a list of unlaunched groups of a user.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account entity.
   * @param array $roles
   *   The user group roles.
   */
  public function getUnlaunchedGroups(AccountInterface $account = NULL, array $roles = ['gdmi-admin']) {
    $unlaunched_groups = [];
    $groups = $this->getAvailableUserGroups($account, $roles);
    
    foreach ($groups as $group) {
      if ($group->field_status->value === 'not_launched') {
        $unlaunched_groups[] = $group;
      }
    }

    return $unlaunched_groups;
  }

  /**
   * Provide an available assessment.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account entity.
   */
  public function getAvailableAssessment(AccountInterface $account = NULL) {
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $assessments_codes = $user->field_gdmi_assessment_code;
    $assessment = NULL;

    foreach ($assessments_codes as $item) {
      if ($item->status === '0') {
        $is_group = $item->group_id !== '0';
        if ($is_group) {
          $availability = $this->checkGroupAvailability($item->group_id);
          $available = $availability['available'];
          if ($available) {
            $assessment = [
              'webform' => $item->webform_id,
              'code' => $item->access_code
            ];
            break;
          }
        } else {
          $assessment = [
            'webform' => $item->webform_id,
            'code' => $item->access_code
          ];
          break;
        }
      }
    }

    return $assessment;
  }

  /**
   * Check group availability.
   *
   * @param int|string|\Drupal\group\Entity\GroupInterface $group_id
   *   The group id.
   */
  public function checkGroupAvailability($group) {
    if (! $group instanceof GroupInterface) {
      $group = $this->entityTypeManager->getStorage('group')->load($group);
    }
    $available = FALSE;
    $start_date_format = NULL;
    $due_date_format = NULL;
    $timezone = NULL;

    if ($group !== NULL) {
      $group_status = $group->field_status->value;
      if ($group_status === 'ongoing') {
        $current_time = new DrupalDateTime('now', $group->field_time_zone->value);
        $start_date = $group->field_start_date->date;
        $due_date = $group->field_due_date->date;
        
        if (!empty($start_date) && !empty($due_date)) {

          $start_date_format = $start_date->format('Y-m-d H:i:s');
          $start_date = new DrupalDateTime($start_date_format, $group->field_time_zone->value);

          $due_date_format = $due_date->format('Y-m-d H:i:s');
          $due_date = new DrupalDateTime($due_date_format, $group->field_time_zone->value);
          
          $available = $current_time->format('U') > $start_date->format('U') && $current_time->format('U') < $due_date->format('U');

          $timezone = $group->field_time_zone->value;
        }
      }
    }

    return  [
      'available' => $available,
      'date' => $start_date_format,
      'end_date' => $due_date_format,
      'timezone' => $timezone
    ];
  }

  /**
   * Update the group primary admin.
   * 
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   * @param \Drupal\user\UserInterface $new_owner
   *   The new group owner.
   * @param \Drupal\user\UserInterface $old_owner
   *   The old group owner.
   */
  public function updateGroupPrimaryAdmin(&$group, $new_owner, $old_owner) {
    $group->setOwner($new_owner);
    $new_owner_membership = $group->getMember($new_owner);
    $group_content = $new_owner_membership->getGroupContent();
    $roles = array_column($group_content->group_roles->getValue(), 'target_id');
    if (($key = array_search('gdmi-admin', $roles)) !== false) {
      unset($roles[$key]);
    }
    $group_content->group_roles->setValue(array_merge($roles, ['gdmi-primary_admin']));
    $group_content->save();
    
    // Update old owner roles.
    $old_owner_membership = $group->getMember($old_owner);
    $group_content = $old_owner_membership->getGroupContent();
    $roles = array_column($group_content->group_roles->getValue(), 'target_id');
    if (($key = array_search('gdmi-primary_admin', $roles)) !== false) {
      unset($roles[$key]);
    }
    $group_content->group_roles->setValue(array_merge($roles, ['gdmi-admin']));
    $group_content->save();
  }

  /**
   * Check if the user has previous assessments.
   * 
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The user account entity.
   * 
   * @return bool
   *   Boolean if has an previous assessments.
   */
  public function userHasPreviousAssessment(AccountInterface $account = NULL) {
    $user = $this->entityTypeManager->getStorage('user')->load($account->id());
    $assessments_codes = $user->field_gdmi_assessment_code;
    return !empty($assessments_codes);
  }

  /**
   * Get the group original purchaser.
   * 
   * @param \Drupal\group\Entity\GroupInterface|int $group
   *   The group.
   */
  public function getGroupPurchaser($group) {
    $user = NULL;
    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
    $orders = $this->entityTypeManager->getStorage('commerce_order')->loadByProperties([
      'state' => 'completed',
      'field_group' => $group instanceof GroupInterface ? $group->id() : $group,
      'type' => 'default'
    ]);
    if (!empty($orders)) {
      $order = reset($orders);
      $user = $order->getCustomer();
    }
    return $user;
  }

  /**
   * Check if there are pending assessments to be complete to the specific group.
   * 
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group.
   */
  public function checkGroupPendingAsssements(GroupInterface $group) {
    $results = \Drupal::database()->query('select * from user__field_gdmi_assessment_code where field_gdmi_assessment_code_group_id = ' . $group->id() . ' and field_gdmi_assessment_code_status = 0;')->fetchAll();
    return !empty($results);
  }
  
}
