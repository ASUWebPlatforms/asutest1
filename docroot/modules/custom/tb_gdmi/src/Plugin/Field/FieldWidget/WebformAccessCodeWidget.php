<?php

namespace Drupal\tb_gdmi\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Plugin implementation of the 'webform_access_code_widget' widget.
 *
 * @FieldWidget(
 *   id = "webform_access_code_widget",
 *   label = @Translation("Webform Access code widget"),
 *   field_types = {
 *     "webform_access_code"
 *   }
 * )
 */
class WebformAccessCodeWidget extends WidgetBase implements ContainerFactoryPluginInterface
{
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new WebformFirstEntityReferenceWidget object.
   *
   * @param string $plugin_id
   *   The plugin ID for the widget.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition object.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param array $settings
   *   The field widget settings.
   * @param array $third_party_settings
   *   Any third party widget settings.
   */
  public function __construct(
    $plugin_id,
    $plugin_definition,
    FieldDefinitionInterface $field_definition,
    EntityTypeManagerInterface $entity_type_manager,
    array $settings,
    array $third_party_settings
  ) {
    parent::__construct(
      $plugin_id,
      $plugin_definition,
      $field_definition,
      $settings,
      $third_party_settings
    );

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $plugin_id,
      $plugin_definition,
      $configuration['field_definition'],
      $container->get('entity_type.manager'),
      $configuration['settings'],
      $configuration['third_party_settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(
      FieldItemListInterface $items,
      $delta,
      Array $element,
      Array &$form,
      FormStateInterface $form_state
  ) {

    $field_name = $this->fieldDefinition->getName();
    $cardinality = $this->fieldDefinition->getFieldStorageDefinition()->getCardinality();
    $element['webform_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Webform'),
      '#description' => $this->t('Select the webform to which the code belongs'),
      '#options' => $this->getWebformOptions(),
      '#default_value' => isset($items[$delta]->webform_id) ? $items[$delta]->webform_id : '',
    ];

    $element['group_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Group ID'),
      '#description' => $this->t('The group ID'),
      '#default_value' => isset($items[$delta]->group_id) ? $items[$delta]->group_id : NULL,
    ];

    $element['submission_id'] = [
      '#type' => 'number',
      '#title' => $this->t('Submission ID'),
      '#description' => $this->t('The submission ID'),
      '#default_value' => isset($items[$delta]->submission_id) ? $items[$delta]->submission_id : NULL,
    ];

    $element['access_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webform access code'),
      '#description' => $this->t('The webform invitation code'),
      '#default_value' => isset($items[$delta]->access_code) ? $items[$delta]->access_code : '',
    ];

    $element['results'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Assessment Encoded Results'),
      '#description' => $this->t('The webform results json encoded'),
      '#default_value' => isset($items[$delta]->results) ? $items[$delta]->results : '',
    ];

    $element['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Completed status'),
      '#default_value' => isset($items[$delta]->status) ? $items[$delta]->status : FALSE,
    ];

    // Add a delete button next to each field item.
    if ($cardinality == FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED) {
      
      $element['remove_button'] = [
        '#type' => 'submit',
        '#attributes' => [
          'class' => ['webform_access_code_remove_button', $field_name . '_remove_button'],
          'data-delta' => $delta,
        ],
        '#value' => $this->t('Remove'),
        '#name' =>  $field_name . '_' . $delta . '_remove_button',
      ];

      $form['#attached']['library'][] = 'tb_gdmi/webform_access_code_remove_button';

    }

    return $element;
  }

  /**
   * Retrieves the options for the Webform select element.
   *
   * @return array
   *   An array of Webform options.
   */
  public function getWebformOptions() {
    $options = [];
    $webforms = $this->entityTypeManager->getStorage('webform')->loadByProperties(['categories.*' => 'GDMI Assessment']);

    foreach ($webforms as $webform) {
      $options[$webform->id()] = $webform->label();
    }

    return $options;
  }

}