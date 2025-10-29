<?php

namespace Drupal\tb_gdmi\Services;

use DateTime;
use DateTimeZone;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\user\UserInterface;
use Drupal\webform\WebformInterface;

/**
 *  GDMI utils service.
 */
class GdmiUtils {
  use StringTranslationTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs the gdmi utils service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, DateFormatterInterface $date_formatter) {
    $this->entityTypeManager = $entity_type_manager;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Provide the latest webform submission of a user.
   *
   * @param string|int $uid
   *   The user id.
   * @param string[] $webforms_id
   *   The webforms the submission belongs to.
   * @return \Drupal\webform\WebformSubmissionInterface
   *   The webform submission entity.
   */
  public function getLastUserSubmission($uid, $webforms_id = ['corporate_gdmi', 'non_corporate_gdmi']) {
    $submission = NULL;
    $submission_storage = $this->entityTypeManager->getStorage('webform_submission');

    $query = $submission_storage->getQuery()
        ->condition('uid', $uid)
        ->condition('webform_id', $webforms_id, 'IN')
        ->condition('in_draft', FALSE)
        ->range(0, 1)
        ->sort('created', 'DESC');

    $submission_ids = $query->execute();

    if (!empty($submission_ids)) {
        $submissions = $submission_storage->loadMultiple($submission_ids);
        $submission = reset($submissions);
    }

    return $submission;
  }

  /**
   * Provide the list of all the webform submission of a user.
   *
   * @param string|int $uid
   *   The user id.
   * @param string[] $webforms_id
   *   The webforms the submission belongs to.
   * @return \Drupal\webform\WebformSubmissionInterface[]
   *   An array of webform submission entity.
   */
  public function geUserSubmissions($uid, $webforms_id = ['corporate_gdmi', 'non_corporate_gdmi']) {
    $submissions = [];
    
    $submission_storage = $this->entityTypeManager->getStorage('webform_submission');

    $query = $submission_storage->getQuery()
        ->condition('uid', $uid,)
        ->condition('webform_id', $webforms_id, 'IN')
        ->condition('in_draft', FALSE)
        ->sort('created', 'DESC');

    $submission_ids = $query->execute();

    if (!empty($submission_ids)) {
      $submissions = $submission_storage->loadMultiple($submission_ids);
    }

    return $submissions;
  }

  /**
   * Provide the list of all the webform submission of a user in select option format.
   *
   * @param string|int $uid
   *   The user id.
   * @param string[] $webforms_id
   *   The webforms the submission belongs to.
   * @return array
   *   An array of webform submission entity.
   */
  public function geUserSubmissionsOptions($uid, $webforms_id = ['corporate_gdmi', 'non_corporate_gdmi']) {
    $options = [];
    
    $submissions = $this->geUserSubmissions($uid, $webforms_id);
    $options = [];
    foreach ($submissions as $key => $submission) {
      $timestamp = $submission->getCreatedTime();
      $options[$key] = $this->dateFormatter->format($timestamp, 'custom', 'F jS, Y') . ' - ' . $submission->getWebform()->label();
    }

    return $options;
  }

  /**
   * Provide a specific webform submission of a user.
   *
   * @param string|int $uid
   *   The user id.
   * @param string|int $sid
   *   The submission id.
   * @param string[] $webforms_id
   *   The webforms the submission belongs to.
   * @return \Drupal\webform\WebformSubmissionInterface
   *   The webform submission entity.
   */
  public function getUserSubmission($uid, $sid, $webforms_id = ['corporate_gdmi', 'non_corporate_gdmi']) {
    $submission = NULL;
    
    $submissions = $this->entityTypeManager->getStorage('webform_submission')
      ->loadByProperties([
        'uid' => $uid,
        'sid' => $sid,
        'webform_id' => $webforms_id
      ]);

    if (!empty($submissions)) {
      $submission = reset($submissions);
    }

    return $submission;
  }

  /**
   * Provide a specific webform submission of a user.
   *
   * @param string|int $media_id
   *   The media id.
   * @param string $intro_text
   *   The intro text.
   * @param string $bottom_text
   *   The bottom text.
   * @param array $items
   *   The capitals items.
   * @param bool $is_main_page
   *   The bottom text.
   * @return array
   *   The rendered array.
   */
  public function getResultsPageContent($media_id, $intro_text, $bottom_text, $items = [], $is_main_page = FALSE) {
    $build = [
      '#image' => [
        '#type' => 'value',
        '#value' => $media_id,
      ],
      '#intro_text' => [
        '#type' => 'processed_text',
        '#text' => $intro_text['value'],
        '#format' => $intro_text['format'],
      ],
      '#is_main_page' => [
        '#type' => 'value',
        '#value' => $is_main_page,
      ],
    ];

    if (!$is_main_page) {
      $build['items'] = $items;
    } else {
      $build['#bottom_text'] = [
        '#type' => 'processed_text',
        '#text' => $bottom_text['value'],
        '#format' => $bottom_text['format'],
      ];
    }

    return $build;
  }

  /**
   * Provide a specific item access code of a group.
   *
   * @param array $access_codes
   *   The access codes list.
   * @param string|int $group_id
   *   The group id.
   * @param string|int $status
   *   The desired code status.
   * @return any
   *   The access code item.
   */
  public function getUserAccessCodeByGroup($access_codes, $group_id, $status = NULL) {
    $access_code = NULL;

    foreach ($access_codes as $item) {
      if ($item->group_id === $group_id) {
        if ($status === NULL) {
          $access_code = $item;
          break;
        } else {
          if ($item->status === $status) {
            $access_code = $item;
            break;
          }
        }
      }
    }

    return $access_code;
  }

  /**
   * Provide a specific item access code of a submission.
   *
   * @param array $access_codes
   *   The access codes list.
   * @param string|int $submission_id
   *   The group id.
   * @param string|int $status
   *   The desired code status.
   * @return any
   *   The access code item.
   */
  public function getUserAccessCodeBySubmission($access_codes, $submission_id) {
    $access_code = NULL;

    foreach ($access_codes as $item) {
      if ($item->submission_id === $submission_id) {
        $access_code = $item;
        break;
      }
    }

    return $access_code;
  }

  /**
   * Provide a specific product by webform id.
   *
   * @param string|int $webform_id
   *   The group id.
   * @return any
   *   The commerce product.
   */
  public function getProductByWebformId($webform_id) {
    $product = NULL;

    $product = $this->entityTypeManager->getStorage('commerce_product')
    ->loadByProperties([
      'type' => 'gdmi_assessment',
      'field_assessment_webform' => $webform_id,
    ]);

    return $product;
  }

   /**
   * Generates a random password.
   *
   * @param int $length
   *   The length of the generated password. Default is 10.
   *
   * @return string
   *   The generated random password.
   */
  public function generateUserPassword($length = 10) {
    $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $password = '';
    $max = strlen($characters) - 1;

    for ($i = 0; $i < $length; $i++) {
      $password .= $characters[random_int(0, $max)];
    }

    return $password;
  }

  /**
   * Formats the given timezone.
   *
   * @param string $timezone
   *   The timezone to format.
   * @return string
   *   The formatted timezone.
   */
  public function formatTimezone($timezone) {
    $now = new \DateTime();
    $tz = new DateTimeZone($timezone);
    $offset = $tz->getOffset($now);

    return $this->t('(UTC@offset_prefix@offset_formatted) @zone', [
      '@offset_prefix' => $offset < 0 ? '-' : '+',
      '@offset_formatted' => gmdate('H:i', abs($offset)),
      '@zone' => str_replace('_', ' ', $timezone),
    ]);
  }

  /**
   * Provide the first access code to a webform.
   *
   * @param string $webform_id
   *   The webform id.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param string $code
   *   The order individual code.
   */
  public function getAssessmentCodeByWebform($webform_id, UserInterface $user, $code) {
    $assessments_codes = $user->field_gdmi_assessment_code;
    foreach ($assessments_codes as $item) {
      if ($item->status === '0' && $item->access_code === $code) {
        if ($item->webform_id == $webform_id) {
          return $item;
        }
      }
    }
    return NULL;
  }

  /**
   * Update a access code item.
   *
   * @param string $code
   *   The webform id.
   * @param \Drupal\user\UserInterface $user
   *   The user.
   * @param array $data
   *   The new values.
   */
  public function updateAssessmentAccessCodeByCode($code, UserInterface &$user, $data) {
    $assessments_codes = $user->get('field_gdmi_assessment_code')->getValue();
    $item_value = NULL;
    foreach ($assessments_codes as &$item) {
      if ($item['access_code'] === $code) {
        
        if (isset($data['group_id'])) {
          $item['group_id'] = $data['group_id'];
        }

        if (isset($data['status'])) {
          $item['status'] = $data['status'];
        }

        if (isset($data['results'])) {
          $item['results'] = $data['results'];
        }

        if (isset($data['submission_id'])) {
          $item['submission_id'] = $data['submission_id'];
        }

        $item_value = $item;
        break;
      }
    }

    $user->set('field_gdmi_assessment_code', $assessments_codes);
    $user->save();

    return $item_value;
  }

  /**
   * Provide the webform first category string.
   *
   * @param \Drupal\webform\WebformInterface $webform
   *   The user.
   * @param string
   *   The category name.
   */
  public function getWebformCategory(WebformInterface $webform) {
    $category = '';
    if ($webform != NULL) {
      $categories = $webform->get('categories');
      if (!empty($categories)) {
        $category = reset($categories);
      }
    }
    return $category;
  }

  /**
   * Creates date from values.
   * 
   * @param integer $day
   *   The day number.
   * @param integer $month
   *   The month number from 1 to 12.
   * @param integer $year
   *   The year number.
   * @param string $time
   *   The 24 hours time format.
   * @param string $time_zone
   *   The default date timezone.
   */
  public function generateDateValue($day, $month, $year, $time, $time_zone) {
    $result = [];
    if (!empty($day) && !empty($month) && !empty($year) && !empty($time)) {

      $dateTime = DateTime::createFromFormat('g:i A', $time);
      $time = $dateTime->format('H:i');
      $time_parts = explode(':', $time);

      try {

        $input['day'] = $day;
        $input['month'] = $month;
        $input['year'] = $year;
        $input['hour'] = $time_parts[0];
        $input['minute'] = $time_parts[1];
        $input['second'] = 0;
        $date = DrupalDateTime::createFromArray($input, $time_zone);
        $result['date'] = $date;

      } catch (\Exception $e) {
        $result['error'] = TRUE;
      }

    }
    
    return $result;
  }

  /**
   * Creates the formatted paragraph reminder label.
   */
  public function reminderLabel($paragraph, $message_type_label = TRUE) {
    $label = '';
    $schedule_type = $paragraph->field_schedule_type->value;
    $message_type = $paragraph->field_message_type->value;
    $message_label = $message_type === 'default' ? 'default message' : 'custom message';
    $days = $paragraph->field_days->value;
    $days_label = $days > 1 ? 'days' : 'day';
    $date = $paragraph->get('field_date')->date;

    if ($schedule_type === 'after') {
      $label = $days . ' ' . $days_label . ' after invite' .  ($message_type_label ? ' with a ' . $message_label : '');
    }

    if ($schedule_type === 'before') {
      $label = $days . ' ' . $days_label . ' before the due date' . ($message_type_label ? ' with a ' . $message_label : '');
    }

    if ($schedule_type === 'datetime') {
      $label = $message_type_label ? 'On a select date ' . $date->format('F j, Y') : $date->format('F j, Y');
    }

    return $label;
  }

  /**
   * Provides the groups communications data reminders and invitations.
   */
  function groupCommunicationsData($group) {
    // Participants Invitation Settings.
    $schedule_type = $group->field_partic_schedule_type->value;
    $schedule_type = $schedule_type === 'datetime' ? $group->field_partic_inv_schedule_date->date->format('j/n/Y') : 'immediately';
    $participants_invite['schedule'] = $schedule_type;
    $message_type = $group->field_partic_invit_msg_type->value;
    $message_type = $message_type === 'custom' ? 'custom message' : 'default message';
    $participants_invite['message_type'] = $message_type;
    
    // Participants Reminders.
    $participants_reminders['reminders_type'] = $group->field_partic_reminders_type->value;
    if($participants_reminders['reminders_type'] === 'custom') {
      $reminders = $group->field_custom_reminders->referencedEntities();
      foreach ($reminders as $reminder) {
        $participants_reminders['items'][] = [
          'schedule' => $this->reminderLabel($reminder, FALSE),
          'message_type' => $reminder->field_message_type->value === 'custom' ? 'custom message' : 'default message'
        ];
      }
    }

    return [
      'participants_invite' => $participants_invite,
      'participants_reminders' => $participants_reminders
    ];
  }

  /**
   * Provides redirect params to routes tb_gdmi.dashboard_groups_communications 
   *  and tb_gdmi.dashboard_groups_add_admins.
   */
  function getCommunicationsRedirectParams($request) {
    $destination = $request->query->get('success');
    $form_query_params = [];

    if ($destination !== NULL) {
      $form_query_params['success'] = $destination;
    }

    $local_task = $request->query->get('local_task');
    if ($local_task  === '1') {
      $form_query_params['local_task'] = $local_task;
    }

    $request_action = $request->query->get('action') ?? 'all';
    if ($request_action !== 'all') {
      $form_query_params['action'] = $request_action;
    }

    return $form_query_params;
  }

  /**
   * Provides labels and backurl to routes tb_gdmi.dashboard_groups_communications.
   */
  function getCommunicationsLabelsAndBackUrl($group, $request) {
    $request_action = $request->query->get('action') ?? 'all';
    $destination = $request->query->get('success');
    
    $data['title'] = $this->t('Communications');
    $data['back_url'] = Url::fromRoute('tb_gdmi.dashboard_groups_schedule', ['group' => $group->id()]);
    $data['summit_text'] = $this->t('SUMMARY');

    if (!empty($destination)) {
      $data['back_url'] = Url::fromUserInput($destination);
      $data['title'] = $this->t('Edit/Add Admin(s)');
      $data['summit_text'] = $this->t('SAVE COMMUNICATIONS');
    }

    if ($request_action === 'communications') {
      $data['title'] = $this->t('Edit Communications');
      $data['summit_text'] = $this->t('SAVE COMMUNICATIONS');
      $data['back_url'] = Url::fromRoute('tb_gdmi.dashboard_groups');
    }

    if ($request_action === 'admins') {
      $data['title'] = $this->t('Edit/Add Admin(s)');
      $data['summit_text'] = $this->t('SAVE ADMINS');
      $data['back_url'] = Url::fromRoute('tb_gdmi.dashboard_groups');
    }

    return $data;
  }

  /**
   * Provides a user if exist with the email.
   */
  function getExistingUser($email) {
    $account_mail = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
    $account_name = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $email]);
    $account = NULL;
    if (!empty($account_mail)) {
      $account = reset($account_mail);
    } elseif (!empty($account_name)) {
      $account = reset($account_name);
    }
    return $account;
  }
}
