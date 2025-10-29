<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\tb_gdmi\Services\GdmiGroups;

/**
 * Provides a GDMI user available webforms Block.
 *
 * @Block(
 *   id = "gdmi_user_available_weforms",
 *   admin_label = @Translation("GDMI User Available Webforms"),
 *   category = @Translation("GDMI"),
 * )
 */
class AvailableWebformsBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
    
    $items = [];

    if ($this->currentUser->isAuthenticated()) {

      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());
      $assessments_codes = $user->field_gdmi_assessment_code;
      
      foreach ($assessments_codes as $item) {
        if ($item->status === '0') {
          $webform = $this->entityTypeManager->getStorage('webform')->load($item->webform_id);
          $is_group = $item->group_id !== '0';
          $type = $is_group ? 'Group' : 'Individual';

          $title =  $webform->label() . ' (' . $type . ')';
          $url = Url::fromRoute('entity.webform.canonical', ['webform' => $webform->id()]);
          $url->setOption('query', ['code' => $item->access_code]);
          $url_string = $url->toString();
          $info = NULL;

          if ($is_group) {
            $group = $this->entityTypeManager->getStorage('group')->load($item->group_id);
            if ($group === NULL) {
              continue;
            }
            $availability = $this->gdmiGroups->checkGroupAvailability($group);
            if (!$availability['available']) {
              continue;
            }
            $url_string = !$availability['available'] ? NULL : $url_string;
            $info = '<small><b>Group Name:</b> ' . $group->label() . '<br><b>Start date:</b> ' . $availability['date']  . '<br><b>End date:</b> ' . $availability['end_date'] . '<br><b>Timezone:</b> ' . $availability['timezone'] . '</small>';
          }

          $items[] = [
            'title' => $title,
            'url' => $url_string,
            'info' => $info
          ];
        }
      }

    }

    return [
      '#theme' => 'gdmi_user_available_weforms',
      '#items' => $items,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
