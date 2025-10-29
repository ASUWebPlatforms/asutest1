<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\tb_gdmi\Services\GdmiUtils;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SubmissionSelectForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;
  
  /**
   * {@inheritdoc}
  */
  public function getFormId() {
    return 'submission_select_form';
  }

  /**
   * Constructs a new SubmissionSelectForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GdmiUtils $gdmi_utils) {
    $this->entityTypeManager = $entity_type_manager;
    $this->gdmiUtils = $gdmi_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('tb_gdmi.gdmi_utils'),
    );
  }

  /**
   * {@inheritdoc}
  */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $current_submission = $this->getRouteMatch()->getParameter('submission_id');
    $options = $this->gdmiUtils->geUserSubmissionsOptions($this->currentUser()->id());

    $form['submission_id'] = [
      '#type' => 'select',
      '#options' => $options,
      '#attributes' => ['onchange' => 'this.form.submit();', 'class' => ['submission-select']],
      '#default_value' => $current_submission,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#attributes' => ['class' => ['visually-hidden']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
  */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submission_id = $form_state->getValue('submission_id');
    $route_match = $this->getRouteMatch();
    
    $params = ['submission_id' => $submission_id];
    $capital_name = $route_match->getParameter('capital_name');
    if ($capital_name) {
      $params['capital_name'] = $capital_name;
    }
    
    $route_name = $route_match->getRouteName();
    $form_state->setRedirect($route_name, $params);
  }
  
}
