<?php

namespace Drupal\tb_gdmi\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure GDMI settings for the purchasing page.
 */
class PurchasingPageSettingsForm extends ConfigFormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($config_factory);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tb_gdmi_purchasing_page_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tb_gdmi.purchasing_page_settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('tb_gdmi.purchasing_page_settings');
    $form['#tree'] = TRUE;

    $form['overview_text'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Overview text.'),
      '#description' => $this->t('The text showed at the top of the page.'),
      '#default_value' => $config->get('overview_text.value'),
      '#format' => 'full_html',
    ];

    $form['items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select the Products to display.'),
    ];
    
    $commerceProducts = $this->entityTypeManager->getStorage('commerce_product')->loadMultiple();
    foreach ($commerceProducts as $commerceProduct) {
      $checkboxId = 'product-checkbox-' . $commerceProduct->id();
      $form['items'][$checkboxId] = [
        '#type' => 'checkbox',
        '#title' => $commerceProduct->getTitle(),
        '#default_value' => in_array($commerceProduct->id(), $config->get('product_items') ?? []),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $product_items = $form_state->getValue('items');
    $items = [];
    foreach ($product_items as $key => $value) {
      if ($value) {
        $productId = str_replace('product-checkbox-', '', $key);
        $items[] = $productId;
      }
    }
    
    $this->config('tb_gdmi.purchasing_page_settings')
      ->set('overview_text', $form_state->getValue('overview_text'))
      ->set('product_items', $items)
      ->save();
    
    parent::submitForm($form, $form_state);
  }

}
