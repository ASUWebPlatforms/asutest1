<?php
namespace Drupal\tb_gdmi\Controller;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Block\BlockManagerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\tb_gdmi\Services\GdmiMailer;
use Drupal\tb_gdmi\Services\GdmiUtils;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Provides route response for the gdmi dashboard.
 */
class DashboardController extends ControllerBase {

  /**
   * The block manager service.
   *
   * @var \Drupal\Core\Block\BlockManagerInterface
   */
  protected $blockManager;

  /**
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * The gdmi mail service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiMailer
   */
  protected $gdmiMailer;

  /**
   * Constructs a new DashboardController object.
   *
   * @param \Drupal\Core\Block\BlockManagerInterface $block_manager
   *   The block plugin manager service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   * @param \Drupal\tb_gdmi\Services\GdmiMailer $gdmi_mailer
   *   The GDMI mailer service.
   */
  public function __construct(BlockManagerInterface $block_manager, GdmiUtils $gdmi_utils, GdmiMailer $gdmi_mailer) {
    $this->blockManager = $block_manager;
    $this->gdmiUtils = $gdmi_utils;
    $this->gdmiMailer = $gdmi_mailer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.block'),
      $container->get('tb_gdmi.gdmi_utils'),
      $container->get('tb_gdmi.gdmi_mailer'),
    );
  }

  /**
   * Returns a dashboard page.
   *
   * @return array
   *   A renderable array.
   */
  public function dashboardPage() {

    $build = [];

    return [
      '#theme' => 'dashboard_page',
      '#children' => $build
    ];
  }

  /**
   * Returns a dashboard results page.
   * 
   * @param string $submission_id
   *   The webformsubmission.
   *
   * @return array
   *   A renderable array.
   */
  public function dashboardResultsPage($submission_id) {
    
    $submission = NULL;
    if ($submission_id === NULL) {
      $submission = $this->gdmiUtils->getLastUserSubmission($this->currentUser()->id());
    } else {
      $submission = $this->gdmiUtils->getUserSubmission($this->currentUser()->id(), $submission_id);
      if ($submission === NULL) {
        throw new NotFoundHttpException();
      }
    }

    // GDMI config page.
    $config = $this->config('tb_gdmi.submission_results_page_settings');
    $content = $this->gdmiUtils->getResultsPageContent(
      $config->get('image'),
      [
        'value' => $config->get('intro_text.value'),
        'format' => $config->get('intro_text.format'),
      ],
      [
        'value' => $config->get('bottom_text.value'),
        'format' => $config->get('bottom_text.format'),
      ],
      $config->get('items'),
      TRUE
    );
    
    // Here logic to include the graphics. Based on the $submission variable.
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());

    if ($submission != NULL) {
      $access_code = $this->gdmiUtils->getUserAccessCodeBySubmission($user->field_gdmi_assessment_code, $submission->id());
      $content['graphics_data'] = json_decode($access_code->results, TRUE);
    }

    return [
      '#theme' => 'dashboard_results_page',
      '#attached' => [
        'library' => [
          'tb_gdmi/circular_graph',
          'tb_gdmi/small_circular_graph'
        ]
      ],
      '#submission' =>  $submission,
      '#content' =>  $content,
    ];
  }

   /**
   * Returns a dashboard results page.
   * 
   * @param string $submission_id
   *   The webformsubmission.
   * @param string $capital_name
   *   The capital term name psychological|intellectual|social|digital.
   *
   * @return array
   *   A renderable array.
   */
  public function dashboardCapitalResultsPage($submission_id, $capital_name) {
    $submission = $this->gdmiUtils->getUserSubmission($this->currentUser()->id(), $submission_id);

    if ($submission === NULL) {
      throw new NotFoundHttpException();
    }

    // GDMI config subpage.
    $config = $this->config('tb_gdmi.submission_results_subpages_settings')->get($capital_name);
    $content = $this->gdmiUtils->getResultsPageContent($config['image'], $config['intro_text'], NULL, $config['items']);

    // Here logic to include the graphics. Based on the $submission variable.
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $access_code = $this->gdmiUtils->getUserAccessCodeBySubmission($user->field_gdmi_assessment_code, $submission->id());
    $data = json_decode($access_code->results, TRUE);
    $content['graphics_data'] = isset($data[$capital_name . '_capital']) ? $data[$capital_name . '_capital'] : [];
    
    $capital_colors = [
      'psychological' => '#E43D51',
      'intellectual' => '#753E96',
      'social' => '#E69E00',
      'digital' => '#0179B7',
    ];
  
    if ($capital_name && isset($capital_colors[$capital_name])) {
      $content['capital_name'] = $capital_name;
      $content['capital_color'] = $capital_colors[$capital_name];
    }

    //Find the group means if is a group type.
    if ($access_code !== NULL && $access_code->group_id !== '0') {
      /** @var \Drupal\group\Entity\GroupInterface $group */
      $group = $this->entityTypeManager->getStorage('group')->load($access_code->group_id);
      if (!empty($group->field_results_data->value)) {
        $group_data = json_decode($group->field_results_data->value, TRUE);
        if (!empty($group_data['means'][$capital_name . '_capital'])) {
          $content['graphics_group_data'] = [
            'capital' => $group_data['means'][$capital_name . '_capital'],
            'participants_completed' => $group_data['participants_completed'],
            'participants_total' => $group_data['participants_total']
          ];
        }
      }
    }

    $grand_means = \Drupal::database()->query("select * from gdmi_grand_means where webform =  '" . $submission->getWebform()->id() . "';")->fetchAll();
    if (!empty($grand_means)) {
      $grand_means_data = json_decode($grand_means[0]->results, TRUE);
      if (!empty($grand_means_data['means'][$capital_name . '_capital'])) {
        $content['graphics_grand_data'] = [
          'capital' => $grand_means_data['means'][$capital_name . '_capital']
        ];
      }
    }

    return [
      '#theme' => 'dashboard_results_page',
      '#submission' =>  $submission,
      '#content' =>  $content,
      '#attached' => [
        'library' => [
          'tb_gdmi/small_circular_graph',
          'tb_gdmi/horizontal_bars_graph',
        ]
      ]
    ];
  }

  /**
   * Returns a dashboard purchasing page.
   * 
   * @return array
   *   A renderable array.
   */
  public function dashboardPurchasingPage() {
    $build = [];
    $config = $this->config('tb_gdmi.purchasing_page_settings');

    $build['available_weforms'] = $this->blockManager->createInstance('gdmi_past_purchases_overview')
      ->build();

    $build['product_selection'] = $this->blockManager->createInstance('gdmi_product_selection', ['items' => $config->get('product_items')])
      ->build();

    return [
      '#theme' => 'dashboard_purchasing',
      '#children' => $build
    ];
  }
  
  /**
   * Resend order receipt to a user.
   * 
   * @param \Drupal\commerce_order\Entity\OrderInterface $commerce_order
   *   The order.
   */
  public function resendOrderReceipt(OrderInterface $commerce_order) {
    $user = $this->entityTypeManager()->getStorage('user')->load($this->currentUser()->id());
    $response = $this->gdmiMailer->orderReceipt($user, $commerce_order);
    if (isset($response['sendResult']) && $response['sendResult'] == 'SENT') {
      $this->messenger()->addStatus('Email Receipt sent successfully.');
    }
    return new RedirectResponse(Url::fromRoute('tb_gdmi.dashboard_purchasing')->toString());
  }
  
  /**
   * Returns a assessment confirmation page.
   * 
   * @return array
   *   A renderable array.
   */
  public function assessmentConfirmationPage() {
    return ['#markup' => '' ];
  }

  /**
   * Restart webform submission draft.
   * 
   * @param \Drupal\webform\WebformSubmissionInterface  $submission
   */
  public function restartWebformDraft(WebformSubmissionInterface $submission) {
    if ($submission->getOwnerId() === $this->currentUser()->id()) {
      $webform = $submission->getWebform();
      $code = $submission->getElementData('webform_invitation_code');
      $submission->delete();
      return new RedirectResponse(Url::fromRoute('entity.webform.canonical', ['webform' => $webform->id()], [ 'query' => ['code' => $code]])->toString());
    }
  }

}