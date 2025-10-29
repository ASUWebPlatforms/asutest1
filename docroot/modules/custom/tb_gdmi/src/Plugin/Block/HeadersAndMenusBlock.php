<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Path\CurrentPathStack;

/**
 * Provides a GDMI Headers and menus Block.
 *
 * @Block(
 *   id = "headers_and_menus",
 *   admin_label = @Translation("GDMI Headers And Menus"),
 *   category = @Translation("GDMI"),
 * )
 */
class HeadersAndMenusBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current path service.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * Constructs a new HeadersAndMenusBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('path.current'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    $configuration = $this->getConfiguration();

    $link = $configuration['link'];

    // Show cancel purchase btn in checkout steps except complete step.
    $attached= [];
    $current_path = $this->currentPath->getPath();
    if (strpos($current_path, '/checkout/') === 0 && !preg_match('/^\/checkout\/\d+\/complete$/', $current_path)) {
      $link = [
        'id' => 'cancel-purchase-btn',
        'title' => $this->t('Cancel purchase')
      ];
      $attached['drupalSettings']['headersAndMenusModal']['btnId'] = 'cancel-purchase-btn';
    }

    return [
      '#theme' => 'headers_and_menus_block',
      '#title' => $configuration['title'],
      '#upper_menu' => $configuration['upper_menu'],
      '#lower_menu' => $configuration['lower_menu'],
      '#link' => $link,
      '#in_user_route' => strpos($current_path, '/user') === 0,
      '#attached' => $attached
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    // Get the list of available menus.
    $menu_options = $this->getMenuOptions();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => $config['title'] ?? NULL,
    ];

    $form['upper_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Select upper menu'),
      '#options' => $menu_options,
      '#default_value' => $config['upper_menu'] ?? NULL,
    ];

    $form['lower_menu'] = [
      '#type' => 'select',
      '#title' => $this->t('Select lower menu'),
      '#options' => $menu_options,
      '#default_value' => $config['lower_menu'] ?? NULL,
    ];

    $form['link'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Extra Button Link'),
      '#description' => $this->t('The cancel creating group button.')
    ];

    $form['link']['uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('URL'),
      '#default_value' => $config['link']['uri'],
      '#maxlength' => 255,
    ];

    $form['link']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link text'),
      '#default_value' => $config['link']['title'],
      '#maxlength' => 255,
    ];

    return $form;
  }

  /**
   * Retrieves the options for the select menu, populated with available menus.
   *
   * @return array
   *   An array of menu options.
   */
  protected function getMenuOptions() {
    $menu_options = [];

    $menu_storage = $this->entityTypeManager->getStorage('menu');
    $menus = $menu_storage->loadMultiple();

    foreach ($menus as $menu) {
      $menu_options[$menu->id()] = $menu->label();
    }

    return $menu_options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);

    $values = $form_state->getValues();
    $this->configuration['title'] = $values['title'];
    $this->configuration['upper_menu'] = $values['upper_menu'];
    $this->configuration['lower_menu'] = $values['lower_menu'];
    $url = $values['link']['uri'];
    if (!empty($url)) {
      if (!UrlHelper::isExternal($url)){
        $url = Url::fromUserInput($values['link']['uri'])->toString();
      } 
    }
    $this->configuration['link']['uri'] = $url;
    $this->configuration['link']['title'] = $values['link']['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => '',
      'upper_menu' => '',
      'lower_menu' => '',
      'link' => [
        'id' => '',
        'uri' => '',
        'title' => '',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts() {
    return Cache::mergeContexts(parent::getCacheContexts(), ['route']);
  }

}
