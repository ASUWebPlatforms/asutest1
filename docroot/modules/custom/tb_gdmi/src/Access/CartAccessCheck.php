<?php
namespace Drupal\tb_gdmi\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Checks if the current user has access to the cart.
 */
class CartAccessCheck implements AccessInterface {

  /**
   * The current logged user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Construct a new CartAccessCheck.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The curren logged user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function access() {
    $current_user = User::load($this->currentUser->id());

    if ($current_user->isAnonymous()) {
      $login_route = Url::fromRoute('user.login');
      $response = new RedirectResponse($login_route->toString());
      $response->send();
      return NULL;
    }

    return AccessResult::allowed();
  }

}