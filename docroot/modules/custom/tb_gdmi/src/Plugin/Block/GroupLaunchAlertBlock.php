<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tb_gdmi\Services\GdmiGroups;

/**
 * Provides a group launch alert Block.
 *
 * @Block(
 *   id = "group_launch_alert",
 *   admin_label = @Translation("GDMI Group Launch Alert"),
 *   category = @Translation("GDMI"),
 * )
 */
class GroupLaunchAlertBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * Constructs a new GroupLaunchAlertBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\tb_gdmi\Services\GdmiGroups $gdmi_groups
   *   The GDMI groups service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, GdmiGroups $gdmi_groups) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->gdmiGroups = $gdmi_groups;
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
      $container->get('current_user'),
      $container->get('tb_gdmi.gdmi_groups')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $link = [];

    $groups = $this->gdmiGroups->getUnlaunchedGroups($this->currentUser);
    if (!empty($groups)) {
      $link['url'] = Url::fromRoute('tb_gdmi.dashboard_groups')->toString();
      $link['text'] = 'Launch Group';
    }

    return [
      '#theme' => 'gdmi_banner_alert',
      '#message' => $this->t('You have a group available to launch!'),
      '#link' => $link,
    ];
  }

   /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $groups = $this->gdmiGroups->getUnlaunchedGroups($this->currentUser);
    if (empty($groups)) {
      return AccessResult::forbidden();
    }
    return parent::access($account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
