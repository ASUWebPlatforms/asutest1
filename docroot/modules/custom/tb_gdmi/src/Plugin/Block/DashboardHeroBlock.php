<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\responsive_background_image\ResponsiveBackgroundImage;
use Drupal\tb_gdmi\Services\GdmiGroups;

/**
 * Provides a GDMI Dashboard Hero Block.
 *
 * @Block(
 *   id = "dashboard_hero_block",
 *   admin_label = @Translation("GDMI Dashboard Hero Block"),
 *   category = @Translation("GDMI"),
 * )
 */
class DashboardHeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The gdmi groups service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiGroups
   */
  protected $gdmiGroups;

  /**
   * Constructs a new AvailableAssessmentAlertBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Drupal\tb_gdmi\Services\GdmiGroups $gdmi_groups
   *   The GDMI groups service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $current_user, GdmiGroups $gdmi_groups) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $current_user;
    $this->gdmiGroups = $gdmi_groups;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('tb_gdmi.gdmi_groups'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    
    $content = [
      'config' => $this->getConfiguration()
    ];
    
    $available = $this->gdmiGroups->getAvailableAssessment($this->currentUser);
    if (!is_null($available)) {
      $url = Url::fromRoute('entity.webform.canonical', ['webform' => $available['webform']]);
      $url->setOption('query', ['code' => $available['code']]);
      $url_string = $url->toString();
      $content['assessment_url'] = $url_string;

      $media = Media::load($this->getConfiguration()['media']);
      if ($media) {
        $file = $media->field_media_image->entity;
    
        $image = \Drupal::service('image.factory')->get($file->getFileUri());
        if ($image->isValid()) {
          $variables['width'] = $image->getWidth();
          $variables['height'] = $image->getHeight();
        }
        else {
          $variables['width'] = $variables['height'] = NULL;
        }
    
        $media_build = [
          '#theme' => 'responsive_image',
          '#width' => $variables['width'],
          '#height' => $variables['height'],
          '#responsive_image_style_id' => 'hero',
          '#uri' => $file->getFileUri(),
        ];
  
        $rendered = \Drupal::service('renderer')->render($media_build);
  
        $content['image'] = $rendered;
      }
    }

    return [
      '#theme' => 'gdmi_dashboard_hero_block',
      '#content' => $content,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['media'] = [
      '#type' => 'media_library',
      '#allowed_bundles' => ['image'],
      '#title' => $this->t('Background image.'),
      '#default_value' => $config['media']
    ];
    
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['title']
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $config['description']
    ];

    $form['btn_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button text'),
      '#default_value' => $config['btn_text']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['media'] = $values['media'];
    $this->configuration['title'] = $values['title'];
    $this->configuration['description'] = $values['description'];
    $this->configuration['btn_text'] = $values['btn_text'];
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'media' => NULL,
      'title' => 'Take the GDMI',
      'description' => '',
      'btn_text' => 'Take GDMI',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account, $return_as_object = FALSE) {
    $available = $this->gdmiGroups->getAvailableAssessment($this->currentUser);
    if (is_null($available)) {
      return AccessResult::forbidden();
    }
    return parent::access($account, $return_as_object);
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
