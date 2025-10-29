<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;

class SetPasswordForm extends FormBase {

  protected $currentUser;

  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  public function getFormId() {
    return 'tb_gdmi_account_confirmation_set_password';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $uid = NULL, $timestamp = NULL, $token = NULL) {
    $account = User::load($uid);
    if (!$account || !$this->isValidToken($account, $timestamp, $token)) {
      $form['error'] = ['#markup' => $this->t('Invalid or expired link.')];
      return $form;
    }

    // Check if the user is already logged in, redirect to dashboard.
    if ($this->currentUser()->isAuthenticated()) {
      $this->messenger()->addMessage('You are already logged in as ' . $this->currentUser()->getEmail());
      $url = Url::fromRoute('tb_gdmi.dashboard', [], ['absolute' => TRUE])->toString();
      return new RedirectResponse($url);
    }

    // Check is user is already active redirect to loging
    if ($account->isActive()) {
      $this->messenger()->addMessage('Your account has already been confirmed. Please log in to continue.');
      $url = Url::fromRoute('user.login', [], ['absolute' => TRUE])->toString();
      return new RedirectResponse($url);
    }

    $form['#prefix'] = '<div class="container mt-4">';
    $form['#suffix'] = '</div>';
    
    $form['intro'] = [
      '#markup' => '<h3 class="form-title">Welcome! Set Your Password</h3><p>Please choose a secure password to activate your account.</p>',
      '#weight' => -10,
    ];
    
    $form['pass'] = [
      '#type' => 'password_confirm',
      '#required' => TRUE,
    ];

    $form['uid'] = ['#type' => 'hidden', '#value' => $uid];
    $form['token'] = ['#type' => 'hidden', '#value' => $token];
    $form['timestamp'] = ['#type' => 'hidden', '#value' => $timestamp];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Set Password and Login'),
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    $uid = $form_state->getValue('uid');
    $token = $form_state->getValue('token');
    $timestamp = $form_state->getValue('timestamp');
    $account = User::load($uid);

    if (!$account || !$this->isValidToken($account, $timestamp, $token)) {
      $this->messenger()->addError($this->t('Invalid or expired token.'));
      return;
    }

    $password = $form_state->getValue('pass');
    $account->setPassword($password);
    $account->activate();
    $account->save();

    user_login_finalize($account);

    $this->messenger()->addMessage('Your account has been confirmed, Thank you!');
    $form_state->setRedirect('tb_gdmi.dashboard');
  }

  private function isValidToken(User $account, $timestamp, $token): bool {
    // Optional: Expire link after 24 hours
    // if ((time() - $timestamp) > 86400) {
    //   return FALSE;
    // }
  
    $hash_salt = Settings::get('hash_salt');
    $expected_token = Crypt::hmacBase64($account->id() . ':' . $timestamp, $hash_salt);
  
    return hash_equals($expected_token, $token);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
