<?php

namespace Drupal\tb_gdmi_reports\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the GDMI report entity edit forms.
 */
class GdmiReportForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $result = parent::save($form, $form_state);

    $entity = $this->getEntity();

    $message_arguments = ['%label' => $entity->toLink()->toString()];
    $logger_arguments = [
      '%label' => $entity->label(),
      'link' => $entity->toLink($this->t('View'))->toString(),
    ];

    switch ($result) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('New GDMI report %label has been created.', $message_arguments));
        $this->logger('tb_gdmi_reports')->notice('Created new GDMI report %label', $logger_arguments);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('The GDMI report %label has been updated.', $message_arguments));
        $this->logger('tb_gdmi_reports')->notice('Updated GDMI report %label.', $logger_arguments);
        break;
    }

    $form_state->setRedirect('entity.gdmi_report.canonical', ['gdmi_report' => $entity->id()]);

    return $result;
  }

}
