<?php
/**
 * @file
 * Theme and preprocess functions.
 */

/**
 * Implements hook_theme().
 */
function tb_gdmi_theme($existing, $type, $theme, $path) {
  return [
    'dashboard_page' => [
      'variables' => [
        'children' => [],
      ],
    ],
    'dashboard_purchasing' => [
      'variables' => [
        'children' => [],
      ],
    ],
    'dashboard_groups_page' => [
      'variables' => [
        'children' => '',
      ],
    ],
    'dashboard_groups_communications' => [
      'variables' => [
        'content' => [],
      ],
    ],
    'dashboard_groups_summary' => [
      'variables' => [
        'content' => [],
      ],
    ],
    'dashboard_results_page' => [
      'variables' => [
        'submission' => NULL,
        'content' => [],
      ],
    ],
    'dashboard_reports_page' => [
      'variables' => [
        'content' => [],
      ],
    ],
    'headers_and_menus_block' => [
      'variables' => [
        'title' => '',
        'upper_menu' => '',
        'lower_menu' => '',
        'link' => [
          'uri' => '',
          'title' => '',
        ],
        'in_user_route' => FALSE,
      ],
    ],
    'gdmi_product_selection' => [
      'variables' => [
        'items' => []
      ]
    ],
    'gdmi_user_available_weforms' => [
      'variables' => [
        'items' => []
      ]
    ],
    'gdmi_user_available_groups' => [
      'variables' => [
        'groups' => []
      ]
    ],
    'gdmi_radios_container' => [
      'render element' => 'element',
    ],
    'gdmi_past_purchases_overview' => [
      'variables' => [
        'content' => []
      ]
    ],
    'gdmi_already_started_assessment' => [
      'variables' => [
        'content' => []
      ]
    ],
    'gdmi_dashboard_hero_block' => [
      'variables' => [
        'content' => []
      ]
    ],
    'gdmi_banner_alert' => [
      'variables' => [
        'message' => '',
        'link' => []
      ]
    ],
    'gdmi_modal' => [
      'variables' => [
        'content' => []
      ],
    ],
    'commerce_checkout_gdmi_completion_pane' => [
      'path' => $path . '/templates/commerce',
      'variables' => [
        'image' => NULL,
        'page_title' => '',
        'pre_title' => '',
        'title' => '',
        'description' => NULL,
        'cta_text' => '',
        'cta_url' => '',
      ],
    ],
    'participant_reminder_form_template' => [
      'template' => 'components/communicationsForm/participant-reminder-template',
      'variables' => [
        'label' => NULL,
        'collapsed' => FALSE,
        'date_options' => [],
        'default_message' => [],
        'index' => NULL,
        'pid' => NULL,
        'edit_enabled' => FALSE,
        'schedule_type' => 'after',
        'days' => 1,
        'date_parts' => [],
        'message_type' => 'default',
        'message_title' => '',
        'message_body' => ''
      ],
    ],
    'group_participants_list' => [
      'template' => 'components/group-participants-list',
      'variables' => [
        'members' => [],
        'edit_btn' => FALSE,
        'order_id' => NULL,
      ],
    ],
    'organization_group_indicator' => [
      'variables' => [
        'group' => NULL,
      ],
    ],
    'commerce_checkout_progress' => [
      'base hook' => 'commerce_checkout_progress',
      'path' => $path . '/templates/commerce',
      'variables' => [
        'steps' => [],
        'purchasing' => TRUE
      ],
    ],
    'commerce_checkout_form__order_information' => [
      'base hook' => 'commerce_checkout_form',
      'path' => $path . '/templates/commerce',
    ],
    'commerce_checkout_form__review' => [
      'base hook' => 'commerce_checkout_form',
      'path' => $path . '/templates/commerce',
    ],
    'commerce_checkout_form__group' => [
      'base hook' => 'commerce_checkout_form',
      'path' => $path . '/templates/commerce',
    ],
    'commerce_checkout_pane__group_details_pane' => [
      'base hook' => 'commerce_checkout_pane',
      'path' => $path . '/templates/commerce',
    ],
    'page__checkout__group' => [
      'base hook' => 'page',
      'path' => $path . '/templates/page',
    ],
    'admins_invitation_designation' => [
      'path' => $path . '/templates/commerce',
      'variables' => [
        'content' => [],
        'message' => [],
        'email' => '',
        'admin_designation_participation' => NULL
      ],
    ],
    'form__group_gdmi_edit_form' => [
      'base hook' => 'form',
      'path' => $path . '/templates/group',
    ],
  ];
}
