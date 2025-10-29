<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tb_gdmi\Services\GdmiUtils;

/**
 * Provides a GDMI past purchases overview Block.
 *
 * @Block(
 *   id = "gdmi_past_purchases_overview",
 *   admin_label = @Translation("GDMI Past Purchases Overview"),
 *   category = @Translation("GDMI"),
 * )
 */
class PastPurchasesOverviewBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

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
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user,
    ConfigFactoryInterface $config_factory, GdmiUtils $gdmi_utils) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
    $this->configFactory = $config_factory;
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
      $container->get('current_user'),
      $container->get('config.factory'),
      $container->get('tb_gdmi.gdmi_utils'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {

    $order_storage = $this->entityTypeManager->getStorage('commerce_order');
    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    /** @var \Drupal\commerce_order\Entity\OrderInterface[] $orders */
    $orders = $order_storage->loadByProperties([
      'state' => 'completed',
      'uid' => $this->currentUser->id(),
      'type' => 'default'
    ]);

    $orders_items = [];

    foreach ($orders as $order) {
      $items = $order->getItems();
      $order_item = reset($items);
      $purchased_entity = $order_item->getPurchasedEntity();
      $product = $purchased_entity->getProduct();
      $webform = $product->field_assessment_webform->entity;
      $type = $purchased_entity->attribute_assessment_type->entity->label();

      if (strtolower($type) === 'group') {
        $group = $order->field_group->entity;
        $orders_items[] = [
          'order' => $order,
          'status' => $group !== NULL ? $group->field_status->value : 'Undefined',
          'type' => 'Group',
        ];
      } else {
        $is_available = $this->gdmiUtils->getAssessmentCodeByWebform($webform->id() , $user, $order->field_individual_code->value);
        $orders_items[] = [
          'order' => $order,
          'status' =>  $is_available ? 'Available': 'Completed',
          'type' => 'Individual',
        ];
      }
    }

    $config = $this->configFactory->get('tb_gdmi.purchasing_page_settings');
    $overview_text = [
      '#type' => 'processed_text',
      '#text' =>  $config->get('overview_text')['value'],
      '#format' => $config->get('overview_text')['format'],
    ];
    
    return [
      '#theme' => 'gdmi_past_purchases_overview',
      '#content' => [
        'orders' => $orders_items,
        'overview_text' => $overview_text,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
