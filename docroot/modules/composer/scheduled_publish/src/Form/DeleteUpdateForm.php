<?php

namespace Drupal\scheduled_publish\Form;

use Drupal\content_moderation\ModerationInformationInterface;
use Drupal\content_moderation\StateTransitionValidationInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityFieldManager;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form for deleting or updating the schedule.
 */
class DeleteUpdateForm extends ConfirmFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected EntityTypeManager $entityTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected EntityFieldManager $entityFieldManager;

  /**
   * State transition validation service.
   *
   * @var \Drupal\content_moderation\StateTransitionValidationInterface
   */
  protected StateTransitionValidationInterface $transitionValidationService;

  /**
   * Content moderation information service.
   *
   * @var \Drupal\content_moderation\ModerationInformationInterface
   */
  protected ModerationInformationInterface $moderationInformationService;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity.
   *
   * @var \Drupal\Core\Entity\ContentEntityBase
   */
  protected ContentEntityBase $entity;

  /**
   * The field name.
   *
   * @var string
   */
  protected string $field;

  /**
   * The field delta (need to find more specific type).
   *
   * @var int
   */
  protected int $fieldDelta;

  /**
   * Constructor for multiple updates form.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\content_moderation\ModerationInformationInterface $moderation_information_service
   *   Content moderation information service.
   * @param \Drupal\content_moderation\StateTransitionValidationInterface $transition_validation_service
   *   The state transition validation service.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current logged in user.
   * @param \Drupal\Core\Messenger\Messenger $messenger
   *   The messenger service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager, ModerationInformationInterface $moderation_information_service, StateTransitionValidationInterface $transition_validation_service, AccountProxyInterface $current_user, Messenger $messenger, RequestStack $request_stack) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
    $this->moderationInformationService = $moderation_information_service;
    $this->transitionValidationService = $transition_validation_service;
    $this->currentUser = $current_user;
    $this->setMessenger($messenger);
    $this->setRequestStack($request_stack);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('content_moderation.moderation_information'),
      $container->get('content_moderation.state_transition_validation'),
      $container->get('current_user'),
      $container->get('messenger'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'scheduled_publish_delete_update_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    $states = $this->entity->get($this->field)->getValue();
    $prev_state = $states[$this->fieldDelta - 1] ?? FALSE;
    if ($prev_state) {
      $orig_status = $this->entity->moderation_state->value;
      $this->entity->moderation_state->value = $prev_state['moderation_state'];
    }
    $m_options = $this->getModerationOptions($this->entity);
    if ($prev_state) {
      $this->entity->moderation_state->value = $orig_status;
    }

    $state_display = $states[$this->fieldDelta]['moderation_state'];
    if (isset($m_options[$states[$this->fieldDelta]['moderation_state']])) {
      $state_display = $m_options[$states[$this->fieldDelta]['moderation_state']];
      $state_display .= ' (';
      $state_display .= $states[$this->fieldDelta]['moderation_state'];
      $state_display .= ')';
    }

    $entity_info = $this->entity->label() . ' (' . $this->entity->id() . ')';
    $date = new DrupalDateTime($states[$this->fieldDelta]['value'], date_default_timezone_get());
    $date_display = $date->format('d.m.Y - H:i');

    return $this->t('Are you sure you want to delete "@state on @date" status update for the "@node" node?',
      [
        '@node' => $entity_info,
        '@state' => $state_display,
        '@date' => $date_display,
      ]);
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return Url::fromRoute('view.scheduled_publish.page_1');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $entity = NULL, $field_delta = NULL) {
    if (!isset($entity) || !isset($field_delta)) {
      $form['message'] = [
        '#theme_wrappers' => ['container'],
        '#markup' => $this->t('A valid entity and field delta must be provided.'),
      ];
      return $form;
    }

    $fields = $this->getScheduledFields($entity->getEntityTypeId(), $entity->bundle());
    $field = reset($fields);
    $states = $entity->get($field)->getValue();
    if (!isset($states[$field_delta])) {
      $form['message'] = [
        '#theme_wrappers' => ['container'],
        '#markup' => $this->t('This status update does not exist.'),
      ];
      return $form;
    }

    $form['message'] = [
      '#theme_wrappers' => ['container'],
      '#markup' => $this->t('If this state deletion invalidates any existing transitions those will be deleted as well.'),
    ];

    // Save data into form_state and class variables.
    $form_state->set(['scheduled_publish', 'entity'], $entity);
    $form_state->set(['scheduled_publish', 'field'], $field);
    $form_state->set(['scheduled_publish', 'field_delta'], $field_delta);
    $this->entity = $entity;
    $this->field = $field;
    $this->fieldDelta = $field_delta;

    return parent::buildForm($form, $form_state);
  }

  /**
   * Returns scheduled publish fields.
   *
   * @param string $entityTypeName
   *   The name of the entity type.
   * @param string $bundleName
   *   The name of the bundle.
   *
   * @return array
   *   Array of scheduled fields.
   */
  protected function getScheduledFields(string $entityTypeName, string $bundleName): array {
    $scheduledFields = [];
    $fields = $this->entityFieldManager->getFieldDefinitions($entityTypeName, $bundleName);
    foreach ($fields as $fieldName => $field) {
      /** @var \Drupal\field\Entity\FieldConfig $field */
      if (strpos($fieldName, 'field_') !== FALSE) {
        if ($field->getType() === 'scheduled_publish') {
          $scheduledFields[] = $fieldName;
        }
      }
    }

    return $scheduledFields;
  }

  /**
   * Get moderation options.
   */
  protected function getModerationOptions($entity) {
    $states = [];
    if ($this->moderationInformationService->isModeratedEntity($entity)) {
      $transitions = $this->transitionValidationService->getValidTransitions($entity, $this->currentUser);
      foreach ($transitions as $key => $value) {
        $states[$transitions[$key]->to()->id()] = $transitions[$key]->label();
      }
    }

    return $states;
  }

  /**
   * Handles state values, clean-up and ordering.
   */
  public function handleStates(FormStateInterface $form_state, &$states) {
    $entity = $form_state->get(['scheduled_publish', 'entity']);
    $orig_status = $entity->moderation_state->value;
    $m_options = $this->getModerationOptions($entity);

    // Make sure states are ordered correctly.
    $this->handleStateOrdering($states);

    foreach ($states as $key => $state) {
      if (isset($m_options[$state['moderation_state']])) {
        $entity->moderation_state->value = $state['moderation_state'];
        $m_options = $this->getModerationOptions($entity);
      }
      else {
        // Delete invalid state changes.
        unset($states[$key]);
      }
    }

    $entity->moderation_state->value = $orig_status;
    // Adjust ordering in case any invalid entries got removed.
    $this->handleStateOrdering($states);
  }

  /**
   * Re-orders states and adds/changes their delta values based on date.
   */
  public static function handleStateOrdering(&$states) {
    usort($states, function ($a, $b) {
      $a_timestamp = strtotime($a['value']);
      $b_timestamp = strtotime($b['value']);
      if ($a_timestamp == $b_timestamp) {
        return 0;
      }
      return ($a_timestamp < $b_timestamp) ? -1 : 1;
    });
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity = $form_state->get(['scheduled_publish', 'entity']);
    $field = $form_state->get(['scheduled_publish', 'field']);
    $delta = $form_state->get(['scheduled_publish', 'field_delta']);
    $states = $entity->get($field)->getValue();

    unset($states[$delta]);
    $this->handleStates($form_state, $states);

    // Reload entity to be sure it's not old.
    $entity = $this->entityTypeManager->getStorage($entity->getEntityTypeId())->load($entity->id());
    $entity->set($field, $states);
    $entity->save();

    $this->messenger()->addStatus($this->t('Status update deleted.'));
  }

}
