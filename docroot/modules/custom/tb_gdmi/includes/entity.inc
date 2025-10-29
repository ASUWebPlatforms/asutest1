<?php
/**
 * @file
 * GDMI entities functions .
 */

use Drupal\Core\Entity\EntityInterface;
use Drupal\webform\WebformSubmissionInterface;
 
/**
 * Implements hook_ENTITY_TYPE_update().
 */
function tb_gdmi_webform_submission_update(EntityInterface $entity) {
  // Basic validation of entity.
  if (!($entity instanceof WebformSubmissionInterface)) {
    return;
  }
  
  if ($entity->isDraft()) {
    return;
  }

  $elements = $entity->getWebform()->getElementsDecodedAndFlattened();

  // Only if current webform has enabled invitations.
  if (isset($elements['webform_invitation_code'])) {
    $data = $entity->getData();
    // Only if code was sent with current submission.
    if (!empty($data['webform_invitation_code'])) {
      // Update code usage in DB.
      $query = \Drupal::database()->update('webform_invitation_codes')->fields([
        'used' => \Drupal::time()->getRequestTime(),
        'sid' => $entity->id(),
      ]);
      $query->condition('code', $data['webform_invitation_code']);
      $query->execute();

      $query = \Drupal::database()->update('user__field_gdmi_assessment_code')->fields([
        'field_gdmi_assessment_code_status' => 1,
      ]);
      $query->condition('field_gdmi_assessment_code_access_code', $data['webform_invitation_code']);
      $query->execute();

    }
  }
}

/**
 * Implements hook_entity_type_alter().
 */
function tb_gdmi_entity_type_alter(array &$entity_types) {
  if(isset($entity_types['group'])){
    $group_type = $entity_types['group'];
    $default_handler_class = $group_type->getHandlerClasses()['form']['edit'];
    $group_type->setFormClass('group_scheduling', $default_handler_class);
  }
}