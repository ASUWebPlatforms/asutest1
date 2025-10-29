<?php
/**
 * @file
 * GDMI reprocess functions .
 */

use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\webform\Utility\WebformElementHelper;

/**
 * Implements template_preprocess_HOOK().
 */
function tb_gdmi_preprocess_block__asu_brand_header(&$variables) {
  if (isset($variables['configuration']['gdmi'])) {
    $gdmi_settings = $variables['configuration']['gdmi'];
    $file_path = '';
    if (!empty($gdmi_settings['logo_gdmi'])) {
      $file = File::load($gdmi_settings['logo_gdmi'][0]);
      if ($file) {
        $file_path = file_create_url($file->getFileUri());
      }
    }
    $gdmi_settings['logo_gdmi'] = $file_path;
    $variables['content']['#attached']['drupalSettings']['asu_brand']['gdmi'] = $gdmi_settings;
  }
}

/**
 * Implements template_preprocess_HOOK() for commerce_checkout_completion_register.
 */
function tb_gdmi_preprocess_commerce_checkout_completion_register(&$variables) {
  $variables["form"]["field_gdmi_assessment_code"]['#access'] = FALSE;
  $variables["form"]["field_domain_admin"]['#access'] = FALSE;
}

/**
 * Implements template_preprocess_HOOK().
 */
function tb_gdmi_preprocess_page_title(&$variables) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {
    $current_route_name = \Drupal::routeMatch()->getRouteName();
    switch ($current_route_name) {
      case 'entity.user.canonical':
        $variables['title'] = [
          '#markup' => '<h1 class="user-gdmi__title">' . t('Account Settings') . '</h1>',
        ];
        break;
      case 'entity.user.edit_form':
        $variables['title'] = [
          '#markup' => '<h1 class="user-gdmi__title">' . t('Edit Account') . '</h1>',
        ];
        break;
      case 'user.login':
      case 'user.register':
        $variables['title'] = NULL;
        break;
    }
  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function tb_gdmi_preprocess_webform_progress(&$variables) {

  if ($variables['current_page'] === 'start_page') {
    return;
  }

  $webform = $variables['webform'];
  $pages = $webform->getElementsInitialized();

  $webform_total_items = [];
  $webform_answered_items = [];
  $page_index = 2;
  foreach ($pages as $page) {

    if ($page['#webform_key'] === 'start_page') {
      continue;
    }

    $webform_total_items = array_merge($webform_total_items, WebformElementHelper::getFlattened($page));

    if ($variables['index'] > $page_index) {
      $webform_answered_items = array_merge($webform_answered_items, WebformElementHelper::getFlattened($page));
    }

    $page_index++;
  }

  // Filter no desired items.
  $webform_total_items = array_filter($webform_total_items, function($element) {
    return $element['#type'] !== 'radios_container' && $element['#type'] !== 'gdmi_notice_message';
  });

  $webform_answered_items = array_filter($webform_answered_items, function($element) {
    return $element['#type'] !== 'radios_container' && $element['#type'] !== 'gdmi_notice_message';
  });

  if (!empty($webform_answered_items)) {
    $variables['percentage'] = (int) round(count($webform_answered_items) * 100 /  count($webform_total_items));
  } else {
    $variables['percentage'] = 0;
  }

}

/**
 * Implements template_preprocess_HOOK() for commerce_checkout_completion_register.
 */
function tb_gdmi_preprocess_page(&$variables) {
  $current_route_name = \Drupal::routeMatch()->getRouteName();
  if ($current_route_name === 'commerce_cart.page' || $current_route_name === 'commerce_checkout.form') {
    $variables['page']['content']['use_wrapper'] = [
      '#type' => 'value',
      '#value' => TRUE
    ];
  }

  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {
    $variables['#attached']['library'][] = 'tb_gdmi/landing_pages';
    $variables['#attached']['library'][] = 'tb_gdmi/gdmi_global';
  }
}


/**
 * Implements hook_preprocess_HOOK() for menu--main.html.twig.
 */
function tb_gdmi_preprocess_menu__gdmi_user(&$variables) {
  $variables['#cache']['contexts'][] = 'url.path';
  $variables['#cache']['contexts'][] = 'url.query_args';
  $route_match = \Drupal::routeMatch();
  $current_path = \Drupal::request()->getRequestUri();
  $webform = $route_match->getParameter('webform');
  $current_route_name = $route_match->getRouteName();
  $gdmi_utils = \Drupal::service('tb_gdmi.gdmi_utils');

  foreach ($variables['items'] as $key => $item) {
    $item_route_name = $item['url']->getRouteName();

    // Dashboard with params
    if (strpos($current_path, '/dashboard?') === 0 && $item_route_name === 'tb_gdmi.dashboard') {
      $variables['items'][$key]['in_active_trail'] = TRUE;
      $variables['items'][$key]['url']->setOption('attributes', ['class' => 'is-active']);
    }

    // Results routes.
    if (strpos($current_path, '/dashboard/results') === 0 && $item_route_name === 'tb_gdmi.dashboard_results') {
      $variables['items'][$key]['in_active_trail'] = TRUE;
      $variables['items'][$key]['url']->setOption('attributes', ['class' => 'is-active']);
    }

    // Manage groups routes.
    if ((strpos($current_path, '/dashboard/groups') === 0 && in_array($item_route_name, ['tb_gdmi.dashboard_groups', 'tb_gdmi.dashboard_groups_schedule']))
    || $item_route_name === 'tb_gdmi.dashboard_groups' && in_array($current_route_name, ['entity.group.edit_form'])) {
      $variables['items'][$key]['in_active_trail'] = TRUE;
      $variables['items'][$key]['url']->setOption('attributes', ['class' => 'is-active']);
    }

    // Purchasing and workflow.
    if ((strpos($current_path, '/checkout/') === 0 && in_array($item_route_name, ['tb_gdmi.dashboard_purchasing']))
    || (strpos($current_path, '/dashboard/purchasing?') === 0 && $item_route_name === 'tb_gdmi.dashboard_purchasing')) {
      $variables['items'][$key]['in_active_trail'] = TRUE;
      $variables['items'][$key]['url']->setOption('attributes', ['class' => 'is-active']);
    }

    // Purchasing and workflow disable others items.
    if (strpos($current_path, '/checkout/') === 0 && !in_array($item_route_name, ['tb_gdmi.dashboard_purchasing']) && !preg_match('/^\/checkout\/\d+\/complete$/', $current_path)) {
      $variables['items'][$key]['url'] = Url::fromRoute('<nolink>');
    }

    // Webform routes.
    if (in_array($item_route_name, ['tb_gdmi.dashboard'])) {
      if (($webform !== NULL && $gdmi_utils->getWebformCategory($webform) === 'GDMI Assessment') || strpos($current_path, '/assessment/confirmation') === 0) {
        $variables['items'][$key]['in_active_trail'] = TRUE;
        $variables['items'][$key]['url']->setOption('attributes', ['class' => 'is-active']);
      }
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for views_view_table.
 */
function tb_gdmi_preprocess_views_view_table(&$variables) {
  $view_id = $variables['view']->id();
  if ($view_id === 'group_members') {
    $variables['attributes']['class'] = 'gdmi-table';
  }
}

/**
 * Implements hook_preprocess_HOOK() for commerce_checkout_progress.
 */
function tb_gdmi_preprocess_commerce_checkout_progress(&$variables) {

  $current_route_name = \Drupal::routeMatch()->getRouteName();
  if ($current_route_name === 'entity.webform.canonical' || $current_route_name === 'tb_gdmi.assessment_confirmation') {
    return;
  }

  $steps = $variables['steps'];
  $track_steps = [];
  foreach ($steps as $step) {
    $track_steps[] = $step;
    if ($step['position'] === 'current') {
      break;
    }
  }

  if ($step['id'] === 'complete') {
    $variables['steps'] = [
      ['id' => 'title', 'label' => t('GDMI Hub')],
      ['id' => 'title', 'label' => t('Purchase')],
      ['id' => 'title', 'label' => t('Purchase Confirmation - Thank you'), 'position' => 'current'],
    ];
    return;
  }

  if ($variables['purchasing']) {
    $variables['steps'] = array_merge([['id' => 'title', 'label' => t('Purchasing')]], $track_steps);
  }
}

/**
 * Implements template_preprocess_region().
 */
function tb_gdmi_preprocess_region(&$variables) {
  // TODO - Separate this in a service.
  if (isset($variables['region']) && $variables['region'] == 'sub_header') {

    $request= \Drupal::request();
    $route_match = \Drupal::routeMatch();
    $webform = $route_match->getParameter('webform');
    $current_route_name = $route_match->getRouteName();
    $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
    $gdmi_utils = \Drupal::service('tb_gdmi.gdmi_utils');

    $build = [
      '#attached' => [
        'library' => ['commerce_checkout/checkout_progress'],
      ],
      '#theme' => 'commerce_checkout_progress',
    ];

    // Assessment.
    if (($webform !== NULL && $gdmi_utils->getWebformCategory($webform) === 'GDMI Assessment' && $theme === 'thunderbird') || $current_route_name === 'tb_gdmi.assessment_confirmation') {
      $build['#steps'] = [
        [
          'id' => 'global-digital-mindset-inventory',
          'label' => 'Global Digital Mindset Inventory',
          'position' => 'next',
        ],
        [
          'id' => 'assessment',
          'label' => 'Assessment',
          'position' => 'current',
        ],
      ];
    }

    if ($theme === 'thunderbird' && $current_route_name === 'tb_gdmi.dashboard_purchasing') {
      $build['#steps'] = [
        [
          'id' => 'select-gdmi-version',
          'label' => 'Select GDMI Version',
          'position' => 'current',
        ],
      ];
    }

    // Group launch.
    $valid_launch_schedule = $current_route_name === 'tb_gdmi.dashboard_groups_schedule' &&  $request->query->get('local_task') !== '1';
    $valid_launch_communications = $current_route_name === 'tb_gdmi.dashboard_groups_communications' &&  $request->query->get('local_task') !== '1' && $request->query->get('action') === NULL;

    if ($valid_launch_schedule ||  $valid_launch_communications || $current_route_name === 'tb_gdmi.dashboard_groups_summary') {
      $build['#purchasing'] = FALSE;
      $build['#steps'] = [
        [
          'id' => 'manage-groups',
          'label' => 'Manage Groups',
          'position' => 'next',
        ],
        [
          'id' => 'launch-group',
          'label' => 'Launch Group',
          'position' => 'next',
        ],
        [
          'id' => 'scheduling',
          'label' => 'Scheduling',
          'position' => 'current',
        ],
      ];
    }


    if ($valid_launch_communications|| $current_route_name === 'tb_gdmi.dashboard_groups_summary') {
      $group = $route_match->getParameter('group');
      $build['#steps'][2]['label'] = Link::createFromRoute('Scheduling', 'tb_gdmi.dashboard_groups_schedule', [
        'group' => $group->id(),
      ])->toString();
      $build['#steps'][2]['potion'] = 'next';

      $build['#steps'][] = [
        'id' => 'communications',
        'label' => 'Communications',
        'position' => 'current',
      ];
    }

    if ($current_route_name === 'tb_gdmi.dashboard_groups_summary') {
      $group = $route_match->getParameter('group');
      $build['#steps'][3]['label'] = Link::createFromRoute('Communications', 'tb_gdmi.dashboard_groups_communications', [
        'group' => $group->id(),
      ])->toString();
      $build['#steps'][3]['potion'] = 'next';

      $build['#steps'][] = [
        'id' => 'group-summary',
        'label' => 'Group Summary',
        'position' => 'current',
      ];
    }

    // Edit group |  Edit group schedule |  Edit admins | Edit communications.
    $currents = [
      'entity.group.edit_form' =>  [
        'id' => 'edit-group',
        'label' => 'Edit Group',
        'position' => 'current',
      ],
      'tb_gdmi.dashboard_groups_schedule' =>  [
        'id' => 'edit-scheduling',
        'label' => 'Edit Scheduling',
        'position' => 'current',
      ],
      'tb_gdmi.dashboard_groups_communications:admins' =>  [
        'id' => 'edit-admins',
        'label' => 'Edit Admins',
        'position' => 'current',
      ],
      'tb_gdmi.dashboard_groups_communications:communications' =>  [
        'id' => 'edit-communications',
        'label' => 'Edit Communications',
        'position' => 'current',
      ],
      'tb_gdmi.group_edit_participants_form' =>  [
        'id' => 'edit-participants',
        'label' => 'Edit Participants',
        'position' => 'current',
      ],
    ];

    if ($current_route_name === 'entity.group.edit_form' ||
      (!$valid_launch_schedule && $current_route_name === 'tb_gdmi.dashboard_groups_schedule') ||
      (!$valid_launch_communications  && $current_route_name === 'tb_gdmi.dashboard_groups_communications') ||
      $current_route_name === 'tb_gdmi.group_edit_participants_form') {

      $build['#purchasing'] = FALSE;
      $current_route_name .= $request->query->get('action') !== NULL ? ':' . $request->query->get('action') : '';

      $build['#steps'] = [
        [
          'id' => 'manage-groups',
          'label' => Link::createFromRoute('Manage groups', 'tb_gdmi.dashboard_groups')->toString(),
          'position' => 'next',
        ],
        $currents[$current_route_name]
      ];
    }

    if (isset($build['#steps'])) {
      $concatenatedHtml = $variables['content'] . \Drupal::service('renderer')->render($build);
      $variables['content']=  Markup::create($concatenatedHtml);
    }

  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function tb_gdmi_preprocess_block__local_tasks_block(&$variables) {
  if (empty(\Drupal::service('domain.negotiator')->getActiveDomain())) {
    return;
  }
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {
    $variables['attributes']['class'][] = 'gdmi-local-tasks';
  }
}

/**
 * Implements template_preprocess_HOOK().
 */
function tb_gdmi_preprocess_menu_local_tasks(&$variables) {
  $theme = \Drupal::service('theme.manager')->getActiveTheme()->getName();
  if ($theme === 'thunderbird') {
    $variables['#cache']['contexts'][] = 'url.path';
    $variables['#cache']['contexts'][] = 'url.query_args';
    $route_match = \Drupal::routeMatch();
    $current_route_name = $route_match->getRouteName();

    $valid_launch_group_schedule = $current_route_name === 'tb_gdmi.dashboard_groups_schedule' &&  \Drupal::request()->query->get('local_task') !== '1';
    $valid_launch_group_communications = $current_route_name === 'tb_gdmi.dashboard_groups_communications' &&  \Drupal::request()->query->get('local_task') !== '1';
    if ($valid_launch_group_schedule || $valid_launch_group_communications) {
      unset($variables['primary']);
    }

    $group = $route_match->getParameter('group');
    if ( $group !== NULL) {

      if ($group->field_status->value === 'ongoing') {
        $variables['primary']['tb_gdmi.group_edit_schedule']['#link']['url'] = Url::fromRoute('tb_gdmi.dashboard_groups_schedule', ['group' => $group->id()],['query' => ['local_task' => 1]]);

        $variables['primary']['tb_gdmi.group_edit_admins']['#link']['url'] = Url::fromRoute('tb_gdmi.dashboard_groups_communications', ['group' => $group->id()],['query' => ['local_task' => 1, 'action' => 'admins']]);
        $variables['primary']['tb_gdmi.group_edit_admins']['#active'] = $current_route_name === 'tb_gdmi.dashboard_groups_communications' && \Drupal::request()->query->get('action') === 'admins';

        $variables['primary']['tb_gdmi.group_edit_communications']['#link']['url'] = Url::fromRoute('tb_gdmi.dashboard_groups_communications', ['group' => $group->id()],['query' => ['local_task' => 1, 'action' => 'communications']]);
        $variables['primary']['tb_gdmi.group_edit_communications']['#active'] = $current_route_name === 'tb_gdmi.dashboard_groups_communications' && \Drupal::request()->query->get('action') === 'communications';

      } else {
        unset($variables['primary']["tb_gdmi.group_edit_schedule"]);
        unset($variables['primary']["tb_gdmi.group_edit_admins"]);
        unset($variables['primary']["tb_gdmi.group_edit_communications"]);
      }

      unset($variables['primary']["views_view:view.group_members.page_1"]);
      unset($variables['primary']["group.edit_form"]);
    }

    if ($current_route_name == 'entity.user.edit_form' || $current_route_name == 'entity.user.canonical') {
      unset($variables['primary']['entity.commerce_payment_method.collection']);
      unset($variables['primary']['views_view:view.commerce_user_orders.order_page']);
    }
  }
}

/**
 * Implements hook_preprocess_HOOK() for menu--main.html.twig.
 */
function tb_gdmi_preprocess_block__asu_footer(&$variables) {
  $current_domain_id = \Drupal::service('domain.negotiator')->getActiveId();
  if ($current_domain_id === 'gdmi') {
    foreach ($variables['elements']['content']['#columns_data'] as $key => $column) {
      $menu_items = $column[0]['menu_items'];
      for ($i=0; $i < count($menu_items); $i++) { 
        $url = $menu_items[$i][0];
        $uri = $url->toString();
        if (!$url->isExternal()) {
          $new_url = Url::fromUri('https://thunderbird.asu.edu' . $uri);
          $variables['content']['#columns_data'][$key][0]['menu_items'][$i][0] =  $new_url;
        }
      }
    }
  }
}
