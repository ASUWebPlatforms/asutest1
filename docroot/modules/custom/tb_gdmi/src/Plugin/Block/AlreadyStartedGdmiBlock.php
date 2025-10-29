<?php

namespace Drupal\tb_gdmi\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;

/**
 * Provides a already started assessment block.
 *
 * @Block(
 *   id = "already_started_gdmi",
 *   admin_label = @Translation("GDMI Already Started Assessment"),
 *   category = @Translation("GDMI"),
 * )
 */
class AlreadyStartedGdmiBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * Constructs a new AvailableAssessmentAlertBlock object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, AccountInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $content = [
      'has_draft' => FALSE,
      'config' => $this->getConfiguration()
    ];

    $draft_submissions = $this->entityTypeManager
      ->getStorage('webform_submission')
      ->loadByProperties([
        'webform_id' => ['corporate_gdmi', 'non_corporate_gdmi'],
        'uid' => $this->currentUser->id(),
        'in_draft' => TRUE,
      ]);

    if (!empty($draft_submissions)) {
      $content['has_draft'] = TRUE;
      $submission = reset($draft_submissions);
      $content['submission'] = $submission;
      $url = Url::fromRoute('entity.webform.canonical', ['webform' => $submission->getWebform()->id()]);
      $url->setOption('query', ['code' => $submission->getElementData('webform_invitation_code')]);
      $url_string = $url->toString();
      $content['assessment_url'] = $url_string;
    }

    return [
      '#theme' => 'gdmi_already_started_assessment',
      '#content' => $content,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();    
    
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['title']
    ];

    $form['continue_gdmi'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Continue GDMI'),
    ];

    $form['continue_gdmi']['continue_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['continue_title']
    ];

    $form['continue_gdmi']['continue_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $config['continue_description']
    ];

    $form['continue_gdmi']['continue_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $config['continue_link_text']
    ];
    
    $form['restart_gdmi'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Restart GDMI'),
    ];

    $form['restart_gdmi']['restart_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['restart_title']
    ];

    $form['restart_gdmi']['restart_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $config['restart_description']
    ];

    $form['restart_gdmi']['restart_link_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Link Text'),
      '#default_value' => $config['restart_link_text']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->configuration['title'] = $values['title'];
    $this->configuration['continue_title'] = $values['continue_gdmi']['continue_title'];
    $this->configuration['continue_description'] = $values['continue_gdmi']['continue_description'];
    $this->configuration['continue_link_text'] = $values['continue_gdmi']['continue_link_text'];
    $this->configuration['restart_title'] = $values['restart_gdmi']['restart_title'];
    $this->configuration['restart_description'] = $values['restart_gdmi']['restart_description'];
    $this->configuration['restart_link_text'] = $values['restart_gdmi']['restart_link_text'];
    parent::blockSubmit($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'title' => 'Already started the GDMI?',
      'continue_title' => 'Continue the GDMI',
      'continue_description' => '',
      'continue_link_text' => 'Continue GDMI',
      'restart_title' => 'Restart the GDMI',
      'restart_description' => '',
      'restart_link_text' => 'Restart GDMI'
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

}
