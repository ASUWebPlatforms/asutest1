<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Provides a GDMI Product selection Block.
 *
 * @Block(
 *   id = "gdmi_product_selection",
 *   admin_label = @Translation("GDMI Product Selection"),
 *   category = @Translation("GDMI"),
 * )
 */
class ProductSelectionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new ProductSelectionBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    $configuration = $this->getConfiguration();

    return [
      '#theme' => 'gdmi_product_selection',
      '#items' => $configuration['items'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();    
    
    $form['items'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Select the Products to display.')
    ];
    
    $commerceProducts = $this->entityTypeManager->getStorage('commerce_product')->loadMultiple();
    foreach ($commerceProducts as $commerceProduct) {
      $checkboxId = 'product-checkbox-' . $commerceProduct->id();
      $form['items'][$checkboxId] = [
        '#type' => 'checkbox',
        '#title' => $commerceProduct->getTitle(),
        '#default_value' => in_array($commerceProduct->id(), $config['items']),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $product_items = $form_state->getValues()['items'];
    $items = [];
    foreach ($product_items as $key => $value) {
      if ($value) {
        $productId = str_replace('product-checkbox-', '', $key);
        $items[] = $productId;
      }
    }
    $this->configuration['items'] = $items;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items' => [],
    ];
  }

}
