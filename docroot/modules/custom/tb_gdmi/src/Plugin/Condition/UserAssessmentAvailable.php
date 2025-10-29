<?php

namespace Drupal\tb_gdmi\Plugin\Condition;

use Drupal\Core\Condition\ConditionPluginBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\tb_gdmi\Services\GdmiGroups;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User Assessment Available' condition.
 *
 * @Condition(
 *   id = "user_assessment_available",
 *   label = @Translation("GDMI User has an assessment available"),
 * )
 */
class UserAssessmentAvailable extends ConditionPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
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
   * The gdmi groups service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiGroups
   */
  protected $gdmiGroups;

  /**
   * Creates a new UserAssessmentAvailable instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\tb_gdmi\Services\GdmiGroups $gdmi_groups
   *   The GDMI groups service.
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager, GdmiGroups $gdmi_groups, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->gdmiGroups = $gdmi_groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('current_user'),
      $container->get('entity_type.manager'),
      $container->get('tb_gdmi.gdmi_groups'),
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    // Define the checkbox to enable the condition.
    $form['show'] = [
        '#title' => $this->t('Display only if the user has an assessment currently available to start'),
        '#type' => 'checkbox',
        // Use whatever value is stored in cofiguration as the default.
        '#default_value' => $this->configuration['show'],
        '#description' => $this->t('When this box is checked, this block will only be shown if the user has an assessment currently available to start, When negated, the block won\'t be shown if the user has an assessment currently available to start.'),
    ];

    return parent::buildConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['show'] = $form_state->getValue('show');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    if ($this->configuration['show']) {
      // Check if the 'negate condition' checkbox was checked.
      if ($this->isNegated()) {
        // The condition is enabled and negated.
        return $this->t('Won\'t be shown if the user has an assessment currently available to start');
      }
      else {
        // The condition is enabled.
        return $this->t('Shown if the user has an assessment currently available to start');
      }
    }
    
    // The condition is not enabled.
    return $this->t('Not Restricted');
  }

  /**
   * {@inheritdoc}
   */
  public function evaluate() {

    if (empty($this->configuration['show']) && !$this->isNegated()) {
      return TRUE;
    }

    $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    $assessments_codes = $user->field_gdmi_assessment_code;
    
    $show = FALSE;
    foreach ($assessments_codes as $item) {
      if ($item->status === '0') {
        
        $is_group = $item->group_id !== '0';
        $type = $is_group ? 'Group' : 'Individual';
        $availability =  $type === 'Individual';

        if ($is_group) {
          $group_availability = $this->gdmiGroups->checkGroupAvailability($item->group_id);
          $availability = $group_availability['available'];
        }

        if ($availability) {
          $show = $availability;
          break;
        }

      }
    }

    return $show;

  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['show' => 0] + parent::defaultConfiguration();
  }

}
