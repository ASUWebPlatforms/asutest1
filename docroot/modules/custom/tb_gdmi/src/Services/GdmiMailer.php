<?php

namespace Drupal\tb_gdmi\Services;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\group\Entity\GroupInterface;
use Drupal\profile\Entity\Profile;
use Drupal\tb_gdmi_hubspot\Services\HubspotTransactionalEmails;
use Drupal\user\UserInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Site\Settings;

/**
 *  GDMI mails service.
 */
class GdmiMailer {

  use StringTranslationTrait;

  /**
   * The mail plugin manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The hubspot emails.
   *
   * @var \Drupal\tb_gdmi_hubspot\Services\HubspotTransactionalEmails
   */
  protected $hubspotEmails;

  /**
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * Constructs the gdmi mailer service.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail plugin manager.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\tb_gdmi_hubspot\Services\HubspotTransactionalEmails $hubspot_emails
   *   The Hubspot emails service.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   */
  public function __construct(MailManagerInterface $mail_manager, MessengerInterface $messenger, HubspotTransactionalEmails $hubspot_emails, GdmiUtils $gdmi_utils) {
    $this->mailManager = $mail_manager;
    $this->messenger = $messenger;
    $this->hubspotEmails = $hubspot_emails;
    $this->gdmiUtils = $gdmi_utils;
  }

  /**
   * Sends a invitation one-time login link to the user via email.
   *
   * @param \Drupal\user\Entity\UserInterface $account
   *   The user account entity to send the email to.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group where the user was added.
   */
  public function notifyUser(UserInterface $account, GroupInterface $group, $absolute = TRUE) {
    $to = $account->getEmail();
    $due_date = $group->field_due_date->date;
    $start_date = $group->field_start_date->date;
    
    $timestamp = time();
    $secret = Settings::get('hash_salt');
    $token = Crypt::hmacBase64($account->id() . ':' . $timestamp, $secret);

    $link = Url::fromRoute('tb_gdmi.account_confirmation_confirm', [
      'uid' => $account->id(),
      'token' => $token,
      'timestamp' => $timestamp,
    ], ['absolute' => $absolute])->toString();

    $link = $absolute ? $link : 'https://gdmi.thunderbird.asu.edu' . $link;
    $timezone = $group->field_time_zone->value;

    $data = [
      [
        'name' => 'name',
        'value' => $account->get('field_first_name')->value . ' ' . $account->get('field_last_name')->value
      ],
      [
        'name' => 'startDate',
        'value' => $start_date !== NULL ? $start_date->format('Y-m-d') : 'Undefined'
      ],
      [
        'name' => 'startTime',
        'value' => $start_date !== NULL ? $start_date->format('H:i') : 'Undefined'
      ],
      [
        'name' => 'closeDate',
        'value' => $due_date !== NULL ? $due_date->format('Y-m-d') : 'Undefined'
      ],
      [
        'name' => 'time',
        'value' => $due_date !== NULL ? $due_date->format('H:i') : 'Undefined'
      ],
      [
        'name' => 'timezone',
        'value' => $timezone
      ],
      [
        'name' => 'link',
        'value' => $link
      ]
    ];

    return $this->hubspotEmails->sendEmail($to, 182816405061, $data);
  }

  /**
   * Sends a reminder to the user via email.
   *
   * @param \Drupal\user\Entity\UserInterface $account
   *   The user account entity to send the email to.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group where the user was added.
   */
  public function reminderUser(UserInterface $account, GroupInterface $group, $absolute = FALSE) {
    $to = $account->getEmail();
    $due_date = $group->field_due_date->date;
    $timezone = $group->field_time_zone->value;

    $access_codes = $account->field_gdmi_assessment_code;
    $group_access_code = \Drupal::service('tb_gdmi.gdmi_utils')->getUserAccessCodeByGroup($access_codes, $group->id());
 
    if ($group_access_code !== NULL) {
      $url = Url::fromRoute('entity.webform.canonical', ['webform' => $group_access_code->webform_id]);
      $url->setOption('query', ['code' => $group_access_code->access_code]);
      $url->setAbsolute($absolute);
      $link = $url->toString();
      
      $link = $absolute ? $link : 'https://gdmi.thunderbird.asu.edu' . $link;
      
      $data = [
        [
          'name' => 'name',
          'value' => $account->get('field_first_name')->value . ' ' . $account->get('field_last_name')->value
        ],
        [
          'name' => 'dueDate',
          'value' => $due_date->format('Y-m-d H:i') . ' ' . $timezone
        ],
        [
          'name' => 'link',
          'value' => $link
        ]
      ];
  
      return $this->hubspotEmails->sendEmail($to, 182994149167, $data);
    }
  }

  /**
   * Sends admin invitation user via email.
   *
   * @param \Drupal\user\Entity\UserInterface $account
   *   The user account entity to send the email to.
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group where the user was added.
   */
  public function notifyUserAdmin(UserInterface $account, GroupInterface $group) {
    $to = $account->getEmail();
    
    $timestamp = time();
    $secret = Settings::get('hash_salt');
    $token = Crypt::hmacBase64($account->id() . ':' . $timestamp, $secret);

    $link = Url::fromRoute('tb_gdmi.account_confirmation_confirm', [
      'uid' => $account->id(),
      'token' => $token,
      'timestamp' => $timestamp,
    ], ['absolute' => TRUE])->toString();

    $data = [
      [
        'name' => 'name',
        'value' => $account->get('field_first_name')->value . ' ' . $account->get('field_last_name')->value
      ],
      [
        'name' => 'group',
        'value' => $group->label()
      ],
      [
        'name' => 'organization',
        'value' => $group->field_organization->value
      ],
      [
        'name' => 'link',
        'value' => $link
      ]
    ];

    return $this->hubspotEmails->sendEmail($to, 182993422394, $data);
  }

  /**
   * Sends a order receipt email to a user.
   *
   * @param \Drupal\user\Entity\UserInterface $account
   *   The user account entity to send the email to.
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The order type individual or group.
   */
  public function orderReceipt(UserInterface $account, OrderInterface $order) {
    $to = $account->getEmail();
    $created_timestamp = $order->getCreatedTime();
    $created_timestamp = $created_timestamp ?? \Drupal::time()->getCurrentTime();
    $formatted_date = \Drupal::service('date.formatter')->format($created_timestamp, 'custom', 'Y-m-d');
    $group = $order->field_group->entity;
    
    $data = [
      [
        'name' => 'date',
        'value' => $formatted_date
      ],
      [
        'name' => 'amount',
        'value' => $order->getTotalPrice()->__toString()
      ],
      [
        'name' => 'description',
        'value' => $this->getOrderDescription($order)
      ],
      [
        'name' => 'group',
        'value' => $group != NULL ? $group->label() : 'Individual Assessment'
      ],
      [
        'name' => 'billingInfo',
        'value' => $this->getBillingInfo($order)
      ]
    ];
    return $this->hubspotEmails->sendEmail($to, 182994683650, $data);
  }

  /**
   * Sends reset password email to a user.
   *
   * @param \Drupal\user\Entity\UserInterface $account
   *   The user account entity to send the email to.
   */
  public function resetPassword(UserInterface $account) {
    $to = $account->getEmail();
    $link = user_pass_reset_url($account);
    $data = [
      [
        'name' => 'name',
        'value' => $account->get('field_first_name')->value . ' ' . $account->get('field_last_name')->value
      ],
      [
        'name' => 'email',
        'value' => $to
      ],
      [
        'name' => 'link',
        'value' => $link
      ]
    ];
    
    return $this->hubspotEmails->sendEmail($to, 182993424911, $data);
  }

  /**
   * Sends reset password email to a user.
   *
   * @param \Drupal\user\Entity\UserInterface $account
   *   The user account entity to send the email to.
   */
  public function accountConfirmation(UserInterface $account) {
    $to = $account->getEmail();
    $data = [
      [
        'name' => 'name',
        'value' => $account->get('field_first_name')->value . ' ' . $account->get('field_last_name')->value
      ]
    ];
    return $this->hubspotEmails->sendEmail($to, 182996955753, $data);
  }

  /**
   * Sends assessment completed email to a user.
   *
   * @param \Drupal\user\Entity\UserInterface $account
   *   The user account entity to send the email to.
   * @param \Drupal\webform\WebformSubmissionInterface $webform_submission
   *   The webform submission entity.
   */
  public function assessmentCompleted(UserInterface $account, WebformSubmissionInterface $webform_submission) {
    $to = $account->getEmail();
    $link = Url::fromRoute('tb_gdmi.dashboard_results', ['submission_id' => $webform_submission->id()], ['absolute' => true])->toString();
    $data = [
      [
        'name' => 'link',
        'value' => $link
      ]
    ];
    $this->hubspotEmails->sendEmail($to, 182993424000, $data);
  }

  /**
   * Generates a one-time login link for a user account.
   *
   * @param \Drupal\user\UserInterface $account
   *   The user account for which the login link will be generated.
   * @param string $destination
   *   (optional) The destination path the user will be redirected to after logging in.
   *   Defaults to '/dashboard/groups'.
   *
   * @return string
   *   A URL string representing the one-time login link.
   */
  public function createLoginLink($account, $absolute = TRUE, $destination = NULL) {
    $timestamp = \Drupal::time()->getRequestTime();

    $options = [
      'absolute' => $absolute,
      'language' => \Drupal::languageManager()->getLanguage($account->getPreferredLangcode()),
    ];

    if ($destination !== NULL) {
      $options['query'] = ['destination' => $destination];
    }

    $link = Url::fromRoute(
      'user.reset.login',
      [
        'uid' => $account->id(),
        'timestamp' => $timestamp,
        'hash' => user_pass_rehash($account, $timestamp),
      ],
      $options
    )->toString();

    return $link;
  }


  /**
   * Generates an order description by combining the titles of purchased products.

   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity for which the description will be generated.
   * @return string
   *   A description of the order.
   */
  function getOrderDescription(OrderInterface $order) {
    $line_items = $order->getItems();
    $descriptions = [];
    foreach ($line_items as $line_item) {
      $purchased_entity = $line_item->getPurchasedEntity();
      if ($purchased_entity) {
        $descriptions[] = $line_item->getQuantity() . ' x ' . $purchased_entity->label();
      }
    }
    return !empty($descriptions) ? implode(', ', $descriptions) : 'No description available.';
  }


  /**
   * Retrieves and formats the billing information for an order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   * @return string
   *   A string containing the billing information.
   */
  function getBillingInfo(OrderInterface $order) {
    $billing_profile = $order->getBillingProfile();
    if (!$billing_profile instanceof Profile) {
      return 'No billing information available.';
    }

    /** @var \Drupal\address\Plugin\Field\FieldType\AddressItem|NULL $address */
    $address = $billing_profile->get('address')->first();
    if (!$address) {
      return 'No billing address available.';
    }

    $formatted_address = [
      'Name: ' . $address->get('given_name')->getValue() . ' ' . $address->get('family_name')->getValue(),
      'Organization: ' . $address->get('organization')->getValue(), 
      'Address: ' . $address->get('address_line1')->getValue(), 
      $address->get('address_line2')->getValue(),
      'Postal Code: ' . $address->get('postal_code')->getValue(),
      'Country Code:' . $address->get('country_code')->getValue(),
    ];

    return implode("\n", array_filter($formatted_address));
  }

  public function sendParticipantsInvitations(GroupInterface $group, $participants, $absolute = TRUE) {
    foreach ($participants as $participant) {
      $user = $participant->getUser();
      $code = $this->gdmiUtils->getUserAccessCodeByGroup($user->field_gdmi_assessment_code, $group->id());
      if ($code !== NULL && $code->status !== '1') {
        $this->notifyUser($user, $group, $absolute);
      }
    }
  }

  public function sendParticipantsReminders($participants, $group, $absolute = FALSE) {
    foreach ($participants as $participant) {
      $user = $participant->getUser();
      $code = $this->gdmiUtils->getUserAccessCodeByGroup($user->field_gdmi_assessment_code, $group->id());
      if ($code !== NULL && $code->status !== '1') {
        $this->reminderUser($participant->getUser(), $group, $absolute);
      }
    }
  }

}
