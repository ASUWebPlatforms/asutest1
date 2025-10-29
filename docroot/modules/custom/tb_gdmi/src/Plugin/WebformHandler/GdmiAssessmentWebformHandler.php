<?php

namespace Drupal\tb_gdmi\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Webform submission gdmi assessment handler.
 *
 * @WebformHandler(
 *   id = "gdmi_assessment",
 *   label = @Translation("GDMI assessment handler"),
 *   category = @Translation("GDMI"),
 *   description = @Translation("Trigger an action on a GDMI assessment submission."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class GdmiAssessmentWebformHandler extends WebformHandlerBase {

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
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * The gdmi groups service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiGroups
   */
  protected $gdmiGroups;
  
  /**
   * The GDMI assessment calculation service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiAssessmentCalculation
   */
  protected $gdmiAssessmentCalculation;

  /**
   * The gdmi mail service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiMailer
   */
  protected $gdmiMailer;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->gdmiUtils = $container->get('tb_gdmi.gdmi_utils');
    $instance->gdmiAssessmentCalculation = $container->get('tb_gdmi.gdmi_assessment_calculation');
    $instance->gdmiGroups = $container->get('tb_gdmi.gdmi_groups');
    $instance->gdmiMailer = $container->get('tb_gdmi.gdmi_mailer');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    $state = $webform_submission->getState();

    if ($state === 'completed') {

      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

      $results = $this->gdmiAssessmentCalculation->calculate($webform_submission);
      $encoded_results = json_encode($results);
      
      // Set Assessment Access Code as completed.
      $new_data = ['status' => '1', 'results' => $encoded_results, 'submission_id' => $webform_submission->id()];
      $access_code = $this->gdmiUtils->updateAssessmentAccessCodeByCode($webform_submission->getElementData('webform_invitation_code'), $user, $new_data);
      
      // Group mean calculation.
      if ($access_code !== NULL && $access_code['group_id'] !== '0') {
        /** @var \Drupal\group\Entity\GroupInterface $group */
        $group = $this->entityTypeManager->getStorage('group')->load($access_code['group_id']);
        $group_results = $this->gdmiAssessmentCalculation->calculateGroup($group);
        $group_encoded_results = json_encode($group_results);
        $group->set('field_results_data', $group_encoded_results);

        // Check group completed.
        if (!$this->gdmiGroups->checkGroupPendingAsssements($group)) {
          $group->set('field_status', 'completed');
        }

        $group->save();
      }

      $this->gdmiAssessmentCalculation->calculateGrandMean($webform_submission->getWebform());

      $this->gdmiMailer->assessmentCompleted($user, $webform_submission);
    }
    
  }

}
