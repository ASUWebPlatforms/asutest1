<?php
namespace Drupal\tb_gdmi\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\file\Entity\File;
use Drupal\tb_gdmi\Services\GdmiUtils;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides route responses for reports module.
 */
class ReportsController extends ControllerBase {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * Constructs a new ReportsController object.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   */
  public function __construct(RendererInterface $renderer, GdmiUtils $gdmi_utils) {
    $this->renderer = $renderer;
    $this->gdmiUtils = $gdmi_utils;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('renderer'),
      $container->get('tb_gdmi.gdmi_utils'),
    );
  }

  /**
   * Returns a reports index page.
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return array
   *   A renderable array.
   */
  public function index(Request $request) {
    $options = $this->gdmiUtils->geUserSubmissionsOptions($this->currentUser()->id());
    $reports = $this->entityTypeManager()->getStorage('gdmi_report')->loadByProperties(['uid' => $this->currentUser()->id()]);
    return [
      '#theme' => 'dashboard_reports_page',
      '#content' => [
        'options' => $options,
        'reports' => $reports,
      ]
    ];
  }

  /**
   * Returns a list of the submission group admins.
   * 
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   */
  public function submissionGroupAdmins(WebformSubmissionInterface $submission) {
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $access_code = $this->gdmiUtils->getUserAccessCodeBySubmission($user->field_gdmi_assessment_code, $submission->id());
    $options = [];
    if ($access_code !== NULL && $access_code->group_id !== '0') {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entityTypeManager->getStorage('group')->load($access_code->group_id);
      $response['group_status'] = $group->field_status->value;
      $admins = $group->getMembers(['gdmi-primary_admin', 'gdmi-admin']);
      foreach ($admins as $admin) {
        $admin_user = $admin->getUser();
        $options[$admin_user->id()] = $admin_user->getEmail();
      }
    }
    $response['options'] = $options;
    return new JsonResponse($response);
  }

  /**
   * Returns the submission results data.
   * 
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   */
  public function getSubmissionResults(WebformSubmissionInterface $submission) {
    $response = [];
    
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $response['user'] = $user->field_first_name->value  . ' ' . $user->field_last_name->value;
    $current_date = new DrupalDateTime('now');
    $response['date'] = $current_date->format('m-d-Y');
    $response['sid'] = $submission->id();
    $access_code = $this->gdmiUtils->getUserAccessCodeBySubmission($user->field_gdmi_assessment_code, $submission->id());
    $response['user_score'] = json_decode($access_code->results, TRUE);
    
    if ($access_code !== NULL && $access_code->group_id !== '0') {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entityTypeManager->getStorage('group')->load($access_code->group_id);
      if ($group->field_results_data->value) {
        $response['group_mean'] = json_decode($group->field_results_data->value, TRUE);
        $response['group_mean']['date'] =  (new DrupalDateTime($response['group_mean']['date']['date']))->format('d/M/Y');
      }
    }
    
    $grand_means = \Drupal::database()->query("select * from gdmi_grand_means where webform =  '" . $submission->getWebform()->id() . "';")->fetchAll();
    if (!empty($grand_means)) {
      $response['grand_mean'] = json_decode($grand_means[0]->results, TRUE);
    }
    
    return new JsonResponse($response);
  }

  /**
   * Returns the submission results data.
   * 
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   */
  public function saveReport(Request $request) {
    $file = $request->files->get('pdf');
    $data = $request->request->all();

    if ($file instanceof UploadedFile) {
      
      $uid = $this->currentUser()->id();
      $directory = 'public://gdmi/reports';
      \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY);

      $filename = \Drupal::service('file_system')->createFilename($file->getClientOriginalName(), $directory);
      $filename = basename($filename);
      $file->move($directory, $filename);

      $file_entity = File::create([
        'filename' => $filename,
        'uri' => $directory . '/' . $filename,
        'status' => 1,
        'uid' => $uid,
      ]);

      $file_entity->save();
      
      \Drupal::service('file.usage')->add($file_entity, 'tb_gdmi', 'tb_gdmi', $uid );

      $user = $this->entityTypeManager()->getStorage('user')->load($uid);
      $access_code = $this->gdmiUtils->getUserAccessCodeBySubmission($user->field_gdmi_assessment_code, $data['sid']);

      $gdmi_report = $this->entityTypeManager()->getStorage('gdmi_report')->create([
        'field_file' => $file_entity->id(),
        'field_type' => $access_code !== NULL && $access_code->group_id !== '0' ? 'group' : 'individual',
        'uid' => $uid,
        'status' => 1,
        'label' => $filename
      ]);

      $gdmi_report->save();

      return new JsonResponse(['gdmi_report' => $gdmi_report->id()]);

    } else {
      return new JsonResponse(['error' => 'No file uploaded'], 400);
    }
  }

}
