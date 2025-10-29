<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tb_gdmi\Services\GdmiGroups;

/**
 * Provides a available assessment alert Block.
 *
 * @Block(
 *   id = "available_assessment_alert",
 *   admin_label = @Translation("GDMI Available Assessment Alert"),
 *   category = @Translation("GDMI"),
 * )
 */
class AvailableAssessmentAlertBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * Constructs a new AvailableAssessmentAlertBlock object.
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
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user, GdmiGroups $gdmi_groups, BlockManagerInterface $block_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->gdmiGroups = $gdmi_groups;
    $this->blockManager = $block_manager;
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
      $container->get('tb_gdmi.gdmi_groups'),
      $container->get('plugin.manager.block'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $link = [];

    $available = $this->gdmiGroups->getAvailableAssessment($this->currentUser);
    if (!is_null($available)) {
      $link['url'] = Url::fromRoute('tb_gdmi.dashboard')->toString();
      $link['text'] = 'Take GDMI';
    }

    return [
      '#theme' => 'gdmi_banner_alert',
      '#message' => $this->t('You have a GDMI available to take!'),
      '#link' => $link,
    ];
  }

   /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $available = $this->gdmiGroups->getAvailableAssessment($this->currentUser);
    if (is_null($available)) {
      return AccessResult::forbidden();
    }

    $group_launch_alert_access =  $this->blockManager->createInstance('group_launch_alert')->access($this->currentUser);
    if (is_bool($group_launch_alert_access) && $group_launch_alert_access) {
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
