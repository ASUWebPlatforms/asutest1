<?php

namespace Drupal\tb_gdmi\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowInterface;
use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the GDMI completion message pane.
 *
 * @CommerceCheckoutPane(
 *   id = "gdmi_complete_pane",
 *   label = @Translation("GDMI complete pane"),
 *   default_step = "complete",
 * )
 */
class GdmiCompletePane extends CheckoutPaneBase {


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, CheckoutFlowInterface $checkout_flow = NULL) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $checkout_flow,
      $container->get('entity_type.manager')
    );
    return $instance;
  }


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'page_title' => 'Thank you for your purchase',
      'group_order' => [
        'image' => NULL,
        'pre_title' => 'Manage Groups',
        'title' => 'Manage Your Group Now',
        'description' => [
          'value' => "The group you have purchased the GDMI for is now able to be managed. You will need to Launch the group, making the decisions of start and due dates, communications, and administration. Proceed to <strong>Manage Groups</strong>, or click the “<strong>Launch Group</strong>” button in the banner above to get started.",
          'format' => 'full_html',
        ],
        'cta' => 'Go to Manage Groups',
      ],
      'individual_order' => [
        'image' => NULL,
        'pre_title' => 'Global Digital Mindset Inventory',
        'title' => 'Go to GDMI to Start',
        'description' => [
          'value' => "You are all ready to take the Global Digital Mindset Inventory (GDMI) assessment. All you need to do is click “Take GDMI” below.",
          'format' => 'basic_html',
        ],
        'cta' => 'Take GDMI',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['#tree'] = TRUE;

    $form['page_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The page title'),
      '#default_value' => $this->configuration['page_title'],
    ];

    $form['group_order'] = [
      '#type' => 'details',
      '#title' => 'Group Order Options',
      '#open' => TRUE,
    ];

    $form['group_order']['image'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image', 'image_block_images'],
      '#title' => $this->t('Image.'),
      '#default_value' =>  $this->configuration['group_order']['image'],
    ];

    $form['group_order']['pre_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The group order pre-title'),
      '#default_value' => $this->configuration['group_order']['pre_title'],
    ];

    $form['group_order']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The group order title'),
      '#default_value' => $this->configuration['group_order']['title'],
    ];

    $form['group_order']['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('The group order description'),
      '#default_value' => $this->configuration['group_order']['description']['value'],
      '#format' => $this->configuration['group_order']['description']['format'],
    ];

    $form['group_order']['cta'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA text'),
      '#default_value' => $this->configuration['group_order']['cta']
    ];

    $form['individual_order'] = [
      '#type' => 'details',
      '#title' => 'Individual Order Options',
      '#open' => TRUE,
    ];

    $form['individual_order']['image'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image', 'image_block_images'],
      '#title' => $this->t('Image.'),
      '#default_value' =>  $this->configuration['individual_order']['image'],
    ];

    $form['individual_order']['pre_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The individual order pre-title'),
      '#default_value' => $this->configuration['individual_order']['pre_title'],
    ];

    $form['individual_order']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('The individual order title'),
      '#default_value' => $this->configuration['individual_order']['title'],
    ];

    $form['individual_order']['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('The individual order description'),
      '#default_value' => $this->configuration['individual_order']['description']['value'],
      '#format' => $this->configuration['individual_order']['description']['format'],
    ];

    $form['individual_order']['cta'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA text'),
      '#default_value' => $this->configuration['individual_order']['cta']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['group_order']['image'] = $values['group_order']['image'];
      $this->configuration['individual_order']['image'] = $values['individual_order']['image'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {

    $order_type = $this->isGroupOrder() ? 'group_order' : 'individual_order';
    $url = $order_type === 'group_order' ? Url::fromRoute('tb_gdmi.dashboard_groups') : Url::fromRoute('tb_gdmi.dashboard');

    $pane_form['build'] = [
      '#theme' => 'commerce_checkout_gdmi_completion_pane',
      '#page_title' => $this->configuration['page_title'],
      '#pre_title' => $this->configuration[$order_type]['pre_title'],
      '#title' => $this->configuration[$order_type]['title'],
      '#description' => [
        '#type' => 'processed_text',
        '#text' => $this->configuration[$order_type]['description']['value'],
        '#format' => $this->configuration[$order_type]['description']['format'],
      ],
      '#cta_text' => $this->configuration[$order_type]['cta'],
      '#cta_url' => $url->toString(),
      '#image' => $this->configuration[$order_type]['image'],
      '#cache' => [
        'max-age' => 0
      ]
    ];

    return $pane_form;
  }

  private function isGroupOrder() {
    if ($this->order->bundle() === 'gdmi_expand_participants') {
      return TRUE;
    }

    $items = $this->order->getItems();
    $item = reset($items);
    $purchased_entity = $item->getPurchasedEntity();
    $type = $purchased_entity->attribute_assessment_type->entity->label();

    return strtolower($type) === 'group';
  }

}
