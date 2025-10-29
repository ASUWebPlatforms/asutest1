<?php
namespace Drupal\tb_gdmi\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;

/**
 * Checks if the current user can edit group members profiles.
 */
class GroupMembersProfileAccessCheck implements AccessInterface {

  /**
   * The current logged user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

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
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Construct a new GdmiAssessmentAccessCheck.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The curren logged user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, GroupMembershipLoaderInterface $membership_loader,
    RouteMatchInterface $route_match) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipLoader = $membership_loader;
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
      
    if ($this->currentUser->hasPermission('administer users')) {
      return AccessResult::allowed();
    }
    
    $user_roles = $this->currentUser->getRoles();
    $is_participant = in_array('gdmi_participant', $user_roles);
    $is_purchaser = in_array('gdmi_purchaser', $user_roles);

    $edit_user = $this->routeMatch->getParameter('user');
    $edit_user = $edit_user instanceof AccountInterface ? $edit_user : $this->entityTypeManager->getStorage('user')->load($edit_user);
    
    if ($is_participant && $edit_user->id() === $this->currentUser->id()) {
      return AccessResult::allowed();
    }

    if (!$is_purchaser) {
      return AccessResult::forbidden();
    }
    
    if ($is_purchaser && $edit_user->id() === $this->currentUser->id()) {
      return AccessResult::allowed();
    }

    $memberships = $this->membershipLoader->loadByUser($this->currentUser, ['gdmi-primary_admin','gdmi-admin']);
    foreach ($memberships as $membership) {
      $group = $membership->getGroup();
      if ($group->getMember($edit_user)) {
        return AccessResult::allowed();
      }
    }

    return AccessResult::forbidden();
  }

}