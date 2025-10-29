<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\group\GroupMembershipLoaderInterface;
use Drupal\tb_gdmi\Services\GdmiGroups;
use Drupal\tb_gdmi\Services\GdmiUtils;

/**
 * Provides a GDMI user available groups Block.
 *
 * @Block(
 *   id = "gdmi_user_available_groups",
 *   admin_label = @Translation("GDMI User Available Groups"),
 *   category = @Translation("GDMI"),
 * )
 */
class AvailableGroupsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The gdmi mail service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiGroups
   */
  protected $gdmiGroups;
  
  /**
   * The gdmi utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * Constructs a new AvailableWebformsBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\group\GroupMembershipLoaderInterface $membership_loader
   *   The group membership loader service.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\tb_gdmi\Services\GdmiGroups $gdmi_groups
   *   The GDMI groups service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager,
    GroupMembershipLoaderInterface $membership_loader, AccountInterface $current_user, GdmiGroups $gdmi_groups, GdmiUtils $gdmi_utils) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->membershipLoader = $membership_loader;
    $this->currentUser = $current_user;
    $this->gdmiGroups = $gdmi_groups;
    $this->gdmiUtils = $gdmi_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('group.membership_loader'),
      $container->get('current_user'),
      $container->get('tb_gdmi.gdmi_groups'),
      $container->get('tb_gdmi.gdmi_utils'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
    $user_groups = $this->gdmiGroups->getAvailableUserGroups($current_user);

    $groups = [];
     /** @var \Drupal\group\Entity\GroupInterface $group */
    foreach ($user_groups as $group) {
      $communications_data = $this->gdmiUtils->groupCommunicationsData($group);
      $group_item = [
        'is_admin' => $group->getOwnerId() === $current_user->id(),
        'group' => $group,
        'admins' => [
          [
            'user' => $group->getOwner(),
            'primary' => TRUE
          ]
        ],
        'communications' => $this->membershipLoader->loadByGroup($group, ['gdmi-primary_admin', 'gdmi-admin']),
        'participants_invite' => $communications_data['participants_invite'],
        'participants_reminders' => $communications_data['participants_reminders']
      ];

      if ($group->field_status->value != 'not_launched') {
        $group_item['timezone_formatted'] = $this->gdmiUtils->formatTimezone($group->field_time_zone->value);
      }
      
      $group_memberships = $this->membershipLoader->loadByGroup($group);
 
      foreach ($group_memberships as $group_membership) {
 
        $access_codes = $group_membership->getUser()->field_gdmi_assessment_code;
        $group_access_code = $this->gdmiUtils->getUserAccessCodeByGroup($access_codes, $group->id());
        
        // Find the gdmi_version.
        if (!isset($group_item['gdmi_version']) && $group_access_code) {
          $product = $this->gdmiUtils->getProductByWebformId($group_access_code->webform_id);
          $group_item['gdmi_version'] = reset($product)->getTitle();
        }

        if (isset($group_membership->getRoles()['gdmi-admin'])) {
          $group_item['is_admin'] = TRUE;
          $admin_user = $group_membership->getUser();
          if ($admin_user->field_first_name->value && $admin_user->field_last_name->value) {
            $group_item['admins'][] = [
              'user' => $admin_user,
              'primary' => FALSE
            ];
          }
        }

        if (isset($group_membership->getRoles()['gdmi-participant'])) {
          $group_item['memberships'][] = [
            'user' => $group_membership->getUser(),
            'status' => $group_access_code->status === '0' ? 'Pending' : 'Finished'
          ];
        }
 
      }
 
      $groups[] = $group_item;
    }
 
    return [
      '#theme' => 'gdmi_user_available_groups',
      '#groups' =>  $groups
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
