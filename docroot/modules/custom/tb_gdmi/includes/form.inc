<?php
/**
 * @file
 * GDMI form functions.
 */

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Url;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\file\Entity\File;
use Drupal\group\Entity\GroupInterface;
use Drupal\media\Entity\Media;
use Drupal\taxonomy\Entity\Term;
use Drupal\user\Entity\User;
use Drupal\user\UserInterface;
use Drupal\webform\Utility\WebformElementHelper;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Implements hook_entity_form_mode_alter().
 */
function tb_gdmi_entity_form_mode_alter(&$form_mode, Drupal\Core\Entity\EntityInterface $entity) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
  $route_name = \Drupal::routeMatch()->getRouteName();
  if ($current_domain_id === 'gdmi' && $theme === 'thunderbird'){
    if ($entity instanceof UserInterface && !$entity->hasRole('administrator')) {
      $form_mode = 'gdmi_edit';
    }
    if ($entity instanceof GroupInterface && $route_name === 'entity.group.edit_form') {
      $form_mode = 'gdmi_edit';
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter() on user_login_form.
 */
function tb_gdmi_form_user_login_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {

    $form['name']['#title'] = t('Email');
    unset($form['name']['#description']);
    unset($form['pass']['#description']);

    $forgot_password = [
      '#type' => 'link',
      '#title' => t('Forgot password?'),
      '#url' => Url::fromRoute('user.pass'),
    ];

    array_unshift($form['actions'], $forgot_password);

    // Get the login form image from configs.
    $image_url = '';
    $media_id = \Drupal::config('tb_gdmi.settings')->get('login_form_media');
    if (isset($media_id )) {
      $media_entity = Media::load($media_id);
      if ($media_entity) {
        $source_value = $media_entity->getSource()->getSourceFieldValue($media_entity);
        $file_entity = File::load($source_value);
        $stream_wrapper_manager = \Drupal::service('stream_wrapper_manager');
        $image_url = $stream_wrapper_manager->getViaUri($file_entity->getFileUri())->getExternalUrl();
      }
    }

    $form['image_url'] = [
      '#type' => 'value',
      '#value' => $image_url
    ];

    $form['#submit'][] = 'tb_gdmi_redirect_user_by_role';

    $request = \Drupal::request();
    $needs_login = $request->query->has('needs_login');
    if ($request->getMethod() === 'GET' && $needs_login) {
      \Drupal::messenger()->addError('You must be logged in to access your assessments.');
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter() on user_login_form.
 */
function tb_gdmi_form_user_register_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {

    unset($form['account']['name']);
    unset($form['account']['pass']['#description']);
    unset($form['account']['mail']['#description']);

    $form['field_first_name']['widget'][0]['value']['#required'] = TRUE;
    $form['field_last_name']['widget'][0]['value']['#required'] = TRUE;
    $form['field_country']['widget'][0]['value']['#required'] = TRUE;
    $form['field_country']['widget']['#after_build'][] = function ($element, &$form_state) {
      $element[0]['value']['country_code']['#options'] = array_merge(['NONE' => 'Select a country'], $element[0]['value']['country_code']['#options']);
      $selected_value = $form_state->getValue('field_country');
      $element[0]['value']['country_code']['#value'] = !empty($selected_value[0]['value']) ? $selected_value[0]['value'] : 'NONE';
      return $element;
    };

    $form['account']['mail']['#title'] = t('Email');
    $form['actions']['submit']['#value'] = t('SUBMIT');

    $form['#validate'] = array_merge(['tb_gdmi_user_register_form_validate'], $form['#validate']);

    $form_message = \Drupal::config('tb_gdmi.settings')->get('register_form_message');
    $form['form_message'] = [
      '#type' => 'processed_text',
      '#text' =>  $form_message['value'],
      '#format' => $form_message['format'],
    ];

    $form['actions']['submit']['#submit'][] = 'tb_gdmi_redirect_user_by_role';
  }
}

/**
 * Custom submit handler redirect user by role.
 */
function tb_gdmi_redirect_user_by_role($form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $current_user = User::load(\Drupal::currentUser()->id());
  if ($current_user->hasRole('gdmi_purchaser') || $current_user->hasRole('gdmi_participant') ) {
    $route_name = $form['#id'] === 'user-login-form' ? 'tb_gdmi.dashboard' : 'tb_gdmi.dashboard_purchasing';
    $form_state->setRedirectUrl(Url::fromRoute($route_name));
  }
}

/**
 * Custom form validation function to set the username value.
 */
function tb_gdmi_user_register_form_validate(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  if ($form_state->getFormObject()->getEntity()->isNew()) {
    // Set the value of the username field equal to the email field and set default role.
    $form_state->setValue('name', $form_state->getValue('mail'));
    $form_state->setValue('roles', ['gdmi_purchaser']);
    $form_state->setValue('field_domain_access', ['gdmi']);

    $country_value = $form_state->getValue('field_country');
    if ($country_value[0]['value'] === 'NONE') {
      $form_state->setErrorByName('field_country', 'Select a valid country.');
    }
  }
}

/*
 * Implements hook_form_alter().
 */
function tb_gdmi_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  if ($form_id === 'webform_ui_element_form') {

    $webform = $form_state->getBuildInfo()['args'][0];

    $categories = $webform->get('categories');
    if (!empty($categories)) {
      $category = reset($categories);
    }

    if ($category === 'GDMI Assessment') {

      $element_key = $form_state->getBuildInfo()['args'][1];
      $element_settings = $webform->getElement($element_key);

      $term = NULL;
      if ($element_settings['#capital_taxonomy']) {
        $term = Term::load($element_settings['#capital_taxonomy']);
      }

      // Add a taxonomy term selection field to the webform UI element form.
      $form['properties']['element']['capital_taxonomy'] = [
        '#type' => 'entity_autocomplete',
        '#title' => t('Capital'),
        '#target_type' => 'taxonomy_term',
        '#selection_settings' => [
          'target_bundles' => ['gdmi_capital'],
        ],
        '#default_value' => $term,
        '#tags' => TRUE,
        '#multiple' => FALSE,
        '#description' => t('Select one from the "Capital" taxonomy.'),
      ];

      $form['#submit'][] = 'tb_gdmi_element_form_submit';

    }
  }
}

/**
 * Custom submit handler for updating the element's settings.
 */
function tb_gdmi_element_form_submit(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $element_key = $form_state->getValue('key');
  $webform = $form_state->getBuildInfo()['args'][0];
  $capital_taxonomy = $form_state->getValue(['capital_taxonomy']);

  $elements = $webform->getElementsDecoded();
  $element = &WebformElementHelper::getElement($elements, $element_key);

  if ($capital_taxonomy !== NULL) {

    if ($element !== NULL) {
      $element['#capital_taxonomy'] = $capital_taxonomy[0]['target_id'];
      $element['#format'] = 'custom';
      $element['#format_html'] = '{{ value }} <a href="/taxonomy/term/' . $capital_taxonomy[0]['target_id'] .'">' . t('Capital'). '</a>';
    }

  } else {

    if ($element !== NULL) {

      unset($element['#capital_taxonomy']);
      unset($element['#format']);
      unset($element['#format_html']);

    }

  }

  $webform->set('elements', $elements);
  $webform->save();
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function tb_gdmi_form_commerce_order_item_add_to_cart_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {

  $order_item = $form_state->getFormObject()->getEntity();
  $product_variation = $order_item->get('purchased_entity')->entity;
  $product = $product_variation->get('product_id')->entity;
  $label = $product->field_assessment_webform->target_id;
  $submit_label = $label === 'corporate_gdmi' ? 'PURCHASE FOR CORPORATE CLIENTS' : 'PURCHASE FOR ACADEMIC CLIENTS';

  $form['actions']['submit']['#value'] = t($submit_label);
  $form['actions']['submit']['#suffix'] = '<i class="fas fa-arrow-right text-white" aria-hidden="true"></i>';
  $form['purchased_entity']['widget'][0]['attributes']['attribute_assessment_type']['#title'] = 'Individual or Group Purchase';

  $options = $form['purchased_entity']['widget'][0]['attributes']['attribute_assessment_type']['#options'];
  if ($options !== NULL) {
    $options = array_merge([ 0 => 'Select purchase type'], $options);
    $form['purchased_entity']['widget'][0]['attributes']['attribute_assessment_type']['#default_value'] = "0";
    $form['purchased_entity']['widget'][0]['attributes']['attribute_assessment_type']['#options'] = $options;
  }
  
  $form['#validate'][] = 'tb_gdmi_validate_add_to_cart';
}

/**
 * Custom form validation callback to limit cart item quantity to 1.
 */
function tb_gdmi_validate_add_to_cart(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  $triggering_element = $form_state->getTriggeringElement();

  if ($triggering_element['#type'] === 'submit') {

    $selected_variation = $form_state->getValue('purchased_entity');
    if ($selected_variation[0]['attributes']['attribute_assessment_type'] === '0') {
      $form_state->setError($form['actions'], t('Please select a purchase type.'));
    }

    $current_user = User::load(\Drupal::currentUser()->id());
    $cart = \Drupal::service('commerce_cart.cart_provider')->getCart('default');

    if ($current_user->isAnonymous()){
      $response = new RedirectResponse(Url::fromRoute('user.login')->toString());
      $response->send();
      return;
    }

    if ($current_user->isAnonymous()){
      $form_state->setError($form['actions'], t('Your user account does not allow you to make a purchase. Please create a separate purchaser account or contact your group admin.'));
    }

    if ($cart !== NULL) {
      $checkout_url = Url::fromRoute('commerce_checkout.form', ['commerce_order' => $cart->id()]);
      $cart_items = $cart->getItems();
      if (!empty($cart_items)) {
        $form_state->setError($form['actions'], t('You already have a purchase in progress. Please complete or cancel your current purchase before starting another.'));
        $response = new RedirectResponse($checkout_url ->toString());
        $response->send();
        return;
      }
    }

  }

}

/**
 * Implements hook_webform_submission_form_alter().
 */
function tb_gdmi_webform_submission_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $webform = $form_state->getFormObject()->getWebform();
  $categories = $webform->get('categories');
  $category = NULL;
  if (!empty($categories)) {
    $category = reset($categories);
  }

  if ($category === 'GDMI Assessment') {

    $pages = $webform->getElementsInitialized();
    $invalid_types = ['radios_container', 'processed_text', 'gdmi_notice_message'];
    unset($form['actions']['draft']);

    $counter = 1;
    foreach ($pages as $page) {

      $page_key = $page['#webform_key'];
      $items = WebformElementHelper::getFlattened($page);

      foreach ($items as $key => $item) {

        if ($key === 'webform_invitation_code' || in_array($item['#type'], $invalid_types)) {
          continue;
        }

        if (isset($item['#webform_parent_key'])) {
          $parent = $webform->getElement($item['#webform_parent_key']);
          if ($parent['#type'] === 'radios_container') {
            $form['elements'][$page_key][$parent['#webform_key']][$key]['#counter'] = $counter . '.';
            $counter++;
            continue;
          }
        }

        $form['elements'][$page_key][$key]['#counter'] = $counter . '.';

        $counter++;
      }

    }

  }

}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function tb_gdmi_form_block_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $block = $form_state->getFormObject()->getEntity();

  if ($block->getPluginId() === 'asu_brand_header') {

    $logo_upload_location = 'public://gdmi/logos/';
    \Drupal::service('file_system')->prepareDirectory($logo_upload_location, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $gdmi_values = $block->get('settings')['gdmi'] ?? NULL;

    $form['settings']['gdmi'] = [
      '#type' => 'details',
      '#title' => t('GDMI configs.'),
      '#description' => t('The GDMI configurations.'),
      '#open' => TRUE,
    ];

    $form['settings']['gdmi']['logo_gdmi'] = [
      '#type' => 'managed_file',
      '#title' => t('GDMI logo.'),
      '#upload_location' =>  $logo_upload_location,
      '#upload_validators' => [
        'file_validate_extensions' => ['gif png jpg jpeg svg'],
      ],
      '#default_value' => $gdmi_values !== NULL && isset($gdmi_values['logo_gdmi']) && !empty($gdmi_values['logo_gdmi']) ? [$gdmi_values['logo_gdmi'][0]] : NULL,
      '#description' => t('Enter the GDMI logo.'),
    ];

    $form['settings']['gdmi']['separation_text'] = [
      '#type' => 'textfield',
      '#title' => t('GDMI logo separation text.'),
      '#default_value' => $gdmi_values !== NULL ?  $gdmi_values['separation_text'] : 'BY',
      '#description' => t('Enter the logos separation text.'),
    ];

    $form['settings']['gdmi']['enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('GDMI feature status.'),
      '#default_value' => $gdmi_values !== NULL ?  $gdmi_values['enabled'] : FALSE,
      '#description' => t('Enable this feature.'),
    ];

    $form['actions']['submit']['#submit'] = array_merge(['tb_gdmi_block_form_asu_brand_header_submit'], $form['#submit']);
  }

  // Add custom block visibility conditions libraries.
  if (isset($form['visibility']['user_previous_order'])) {
    $form['visibility_tabs']['#attached']['library'][] = 'tb_gdmi/user_previous_order';
  }

  if (isset($form['visibility']['user_assessment_available'])) {
    $form['visibility_tabs']['#attached']['library'][] = 'tb_gdmi/user_assessment_available';
  }

  if (isset($form['visibility']['user_completed_assessment'])) {
    $form['visibility_tabs']['#attached']['library'][] = 'tb_gdmi/user_completed_assessment';
  }

}

/**
 * Form submission callback for block_block_configure_form().
 */
function tb_gdmi_block_form_asu_brand_header_submit($form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $block = $form_state->getFormObject()->getEntity();

  if ($block->getPluginId() === 'asu_brand_header') {

    $prev_settings = $block->get('settings');

    $values = $form_state->getValue('settings');
    $block->set('settings', $values);
    $block->save();

    // Save the file as permanent
    if (isset($values['gdmi']['logo_gdmi'])) {
      $file = $values['gdmi']['logo_gdmi'];
      if (!empty($file) && is_numeric($file[0])) {
        $file_entity = File::load($file[0]);
        if ($file_entity !== NULL) {
          $file_usage = \Drupal::service('file.usage');
          $file_usage->add($file_entity, 'tb_gdmi', 'tb_gdmi', \Drupal::currentUser()->id());
        }
      }
    }

    // Delete the prev file.
    if (empty($values['gdmi']['logo_gdmi']) && !empty($prev_settings['gdmi']['logo_gdmi'])) {
      tb_gdmi_load_file_and_delete($prev_settings['gdmi']['logo_gdmi'][0]);
    }

    if (!empty($values['gdmi']['logo_gdmi'])) {
      $file_id = $values['gdmi']['logo_gdmi'][0];
      if (!empty($prev_settings['gdmi']['logo_gdmi']) && $prev_settings['gdmi']['logo_gdmi'][0] !== $file_id) {
        tb_gdmi_load_file_and_delete($prev_settings['gdmi']['logo_gdmi'][0]);
      }
    }

  }

}

/**
 * function to load a file and delete it.
 */
function tb_gdmi_load_file_and_delete($file_id) {
  if ($file_id !== NULL) {
    $file_entity = File::load($file_id);
    if ($file_entity !== NULL) {
      $file_entity->delete();
    }
  }
}

/**
 * Implements hook_form_FORM_ID_alter() on form_commerce_checkout_flow_gdmi_checkout_flow.
 */
function tb_gdmi_form_commerce_checkout_flow_gdmi_checkout_flow_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $form["actions"]['#attributes']['class'][] = 'container';
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function tb_gdmi_form_group_gdmi_group_scheduling_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $form['actions']['submit']['#submit'][] = 'tb_gdmi_group_step_schedule_redirect';
  $form['#validate'][] = 'tb_gdmi_scheduling_form_validate';
  $form['field_start_date']['widget'][0]['value']['#date_timezone'] = DateTimeItemInterface::STORAGE_TIMEZONE;
  $form['field_due_date']['widget'][0]['value']['#date_timezone'] = DateTimeItemInterface::STORAGE_TIMEZONE;
  $form['#after_build'][] = 'tb_gdmi_form_group_gdmi_group_scheduling_callback';
}

/**
 * Custom after build form_group_gdmi_group_scheduling callback.
 */
function tb_gdmi_form_group_gdmi_group_scheduling_callback($form, \Drupal\Core\Form\FormStateInterface $form_state) {

  $fields = ['field_start_date', 'field_due_date'];
  foreach ($fields as $field) {
    $form[$field]['widget'][0]['value']['day']['#required'] = FALSE;
    $form[$field]['widget'][0]['value']['month']['#required'] = FALSE;
    $form[$field]['widget'][0]['value']['year']['#required'] = FALSE;
    $form[$field]['widget'][0]['value']['time']['#required'] = FALSE;
  }

  $form['field_time_zone']['widget'][0]['value']['#required'] = FALSE;
  $form['field_time_zone']['widget'][0]['value']['#title'] = 'Timezone:';
  $form['field_time_zone']['widget'][0]['value']['#attributes']['class'] = ['select2'];

  $form['#attached']['library'][] = 'tb_gdmi/timezone_select2';

  unset($form['advanced']);
  unset($form['actions']['delete']);

  return $form;
}

/**
 * Custom ajax callback group step.
 */
function tb_gdmi_time_zone_callback(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $time_zone = $form_state->getValue('field_time_zone')[0]['value'];
  $form['field_start_date']['widget'][0]['value']['#date_timezone'] = $time_zone;
  $form['field_due_date']['widget'][0]['value']['#date_timezone'] =  $time_zone;

  $output = [
    '#type' => 'container',
    '#children' => [$form['group_dates_container']]
  ];

  return $output;
}

/**
 * Custom submit handler redirect group step.
 */
function tb_gdmi_group_step_schedule_redirect(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $messenger = \Drupal::messenger();
  $is_local_task = \Drupal::request()->query->get('local_task') === '1';
  $group = \Drupal::routeMatch()->getParameter('group');
  $messenger->deleteByType(MessengerInterface::TYPE_STATUS);

  if ($is_local_task) {
    $messenger->addStatus('Group scheduling successfully updated.');
    $form_state->setRedirectUrl(Url::fromRoute('tb_gdmi.dashboard_groups_schedule', ['group' => $group->id(), 'local_task' => 1]));
  } else {
    $messenger->addStatus('Successfully scheduled group.');
    $form_state->setRedirectUrl(Url::fromRoute('tb_gdmi.dashboard_groups_communications', ['group' => $group->id()]));
  }
}

/**
 * Custom validation schedule group.
 */
function tb_gdmi_scheduling_form_validate(&$form, \Drupal\Core\Form\FormStateInterface $form_state) {

  $errors = $form_state->getErrors();
  if (isset($errors['field_time_zone'])) {
    $form_state->clearErrors('field_time_zone');
    $form_state->setErrorByName('field_time_zone', t('The timezone field is required.'));
  }

  if (!$form_state->hasAnyErrors()) {
    $start_date = $form_state->getValue(['field_start_date', 0, 'value']);
    $start_date->setTimezone(new DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));

    $end_date = $form_state->getValue(['field_due_date', 0, 'value']);
    $end_date->setTimezone(new DateTimeZone(DateTimeItemInterface::STORAGE_TIMEZONE));

    if ($start_date->format('U') > $end_date->format('U')) {
      $form_state->setErrorByName('field_start_date', t('Start date cannot be after the end date.'));
    }

    if ($start_date->format('U') == $end_date->format('U')) {
      $form_state->setErrorByName('field_start_date', t('Start date and Due date cannot be the same.'));
    }

  }
}

/**
 * Implements hook_form_FORM_ID_alter().
 */
function tb_gdmi_form_group_gdmi_edit_form_alter(&$form, \Drupal\Core\Form\FormStateInterface $form_state, $form_id) {
  $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
  if ($theme === 'thunderbird'){
    $form['#attributes']['class'][] = 'container';
    $form['label']['widget'][0]['value']['#title'] = t('Group Name');
    $form['field_organization']['widget'][0]['value']['#title'] = t('Organization Name');
    $form['actions']['submit']['#value'] = 'Edit Names';
    unset($form['revision']);
    unset($form['revision_information']);
    unset($form['revision_log_message']);
    $form['#attached']['library'][] = 'tb_gdmi/form_edit_group';
    $form['actions']['submit']['#submit'][] = 'after_build_gdmi_edit_form';
  }
}

function after_build_gdmi_edit_form($form, \Drupal\Core\Form\FormStateInterface $form_state) {
  $group = $form_state->getFormObject()->getEntity();
  $form_state->setRedirect('entity.group.edit_form', ['group' => $group->id()]);
  return $form;
}

/**
 * Implements hook_form_alter().
 */
function tb_gdmi_form_user_form_alter(&$form, &$form_state, $form_id) {
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {
    $form['actions']['submit']['#submit'][] = 'tb_gdmi_user_profile_form_submit';
    $account = $form_state->getFormObject()->getEntity();
    if (!empty($account) && $account->id() != 1) {
      $form_state->set('user_pass_reset', 1);
      $form['account']['current_pass']['#access'] = FALSE;
    }
  }
}

/**
 * Custom submit handler for user profile form.
 */
function tb_gdmi_user_profile_form_submit($form, &$form_state) {
  $user = $form_state->getFormObject()->getEntity();
  $new_email = $form_state->getValue('mail');
  if ($user->getAccountName() !== $new_email) {
    $user->setUsername($new_email);
    $user->save();
  }

  if ($form['#id'] === 'user-register-form') {
    $response = \Drupal::service('tb_gdmi.gdmi_mailer')->accountConfirmation($user);
    if (isset($response['sendResult']) && $response['sendResult'] == 'SENT') {
      \Drupal::messenger()->addMessage('Account confirmation email sent successfully.');
    }
  }
}

/**
 * Implements hook_form_alter().
 */
function tb_gdmi_form_user_pass_alter(&$form, &$form_state, $form_id) {
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {
    $form['#submit'] = [];
    $form['#submit'][] = 'tb_gdmi_user_pass_submit';
  }
}

/**
 * Custom submit handler for the user password form.
 */
function tb_gdmi_user_pass_submit($form, &$form_state) {
  $account = $form_state->getValue('account');
  if ($account != NULL) {
    $response = \Drupal::service('tb_gdmi.gdmi_mailer')->resetPassword($account);
    if (isset($response['sendResult']) && $response['sendResult'] == 'SENT') {
      \Drupal::messenger()->addMessage('User reset password email sent successfully.');
    }
  }
}