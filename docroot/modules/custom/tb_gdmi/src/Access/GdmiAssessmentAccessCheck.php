<?php
namespace Drupal\tb_gdmi\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\tb_gdmi\Services\GdmiGroups;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Checks Gdmi Assessment Access.
 */
class GdmiAssessmentAccessCheck implements AccessInterface {

  /**
   * The current logged user.
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
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The GDMI groups service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiGroups
   */
  protected $gdmiGroups;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Construct a new GdmiAssessmentAccessCheck.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The curren logged user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\tb_gdmi\Services\GdmiGroups $gdmi_groups
   *   The GDMI groups service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(AccountInterface $current_user, RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager, 
    GdmiGroups $gdmi_groups, MessengerInterface $messenger) {
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
    $this->gdmiGroups = $gdmi_groups;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    $webform = $this->routeMatch->getParameter('webform');

    if ($webform == NULL) {
      return AccessResult::allowed();
    }

    $category = '';
    $categories = $webform->get('categories');
    if (!empty($categories)) {
      $category = reset($categories);
    }

    if ($category !== 'GDMI Assessment') {
      return AccessResult::allowed();
    }

    $dashboard_route = Url::fromRoute('tb_gdmi.dashboard');
    $response = new RedirectResponse($dashboard_route->toString());

     /** @var \Drupal\user\UserInterface $current_user */
    $current_user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

    if ($current_user->isAnonymous()) {
      $login_route = Url::fromRoute('user.login', [], ['query' => ['needs_login' => NULL]]);
      $login_response = new RedirectResponse($login_route->toString());
      $login_response->send();
      exit;
    }

    if (!($current_user->hasRole('gdmi_purchaser') || $current_user->hasRole('gdmi_participant') || $current_user->hasRole('administrator'))) {
      return AccessResult::forbidden();
    }

    if ($current_user->hasRole('administrator')) {
      return AccessResult::allowed();
    }
    
    $code = \Drupal::request()->query->get('code');
    if(!isset($code) || empty($code)) {
      $this->messenger->addError('You need a valid access code.');
      $response->send();
      exit;
    }

    $invitation_codes = $current_user->field_gdmi_assessment_code;
    $access_code = NULL;
    foreach ($invitation_codes as $invitation_code) {
      if ($invitation_code->webform_id === $webform->id() && $invitation_code->status === '0' && $invitation_code->access_code === $code) {
        $access_code = $invitation_code;
      }
    }

    if($access_code === NULL) {
      $this->messenger->addError('The access code is invalid.');
      $response->send();
      exit;
    }

    if (!empty($access_code->group_id) && $access_code->group_id !== '0') {
      
      $availability = $this->gdmiGroups->checkGroupAvailability($access_code->group_id);

      if ($availability['available']) {
        return AccessResult::allowed();
      } else {
        $this->messenger->addError('This assessment is not available. [Start Date: ' . $availability['date'] . ' - End Date:' . $availability['end_date'] . ']');
        $response->send();
        exit;
      }

    } else {

      return AccessResult::allowed();
    
    }

  }

}