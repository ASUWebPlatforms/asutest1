<?php

namespace Drupal\tb_migrations\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Thunderbird Migrations settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'tb_migrations_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['tb_migrations.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['aventri'] = [
      '#type' => 'fieldset',
      '#title' => t('Aventri'),
      '#collapsible' => FALSE,
    ];
    
    $form['aventri']['events_updated_date'] = [
      '#type' => 'date',
      '#title' => t('Import events updated after:'),
      '#description' => t('Enter a date to update all events updated since
        that date on each events import. Otherwise, events will only be 
        updated in Drupal if they were updated in Aventri since the last import.'),
      '#default_value' => $this->config('tb_migrations.settings')
        ->get('events_updated_date')
      // '#date_date_format' => 'm/d/Y'
    ];

    $form['aventri']['event_id'] = [
      '#type' => 'textfield',
      '#title' => t('Import event by id:'),
      '#description' => t('Enter event id to update, if the Event exist it will be updated and if not it will be created. (Event must be Live in Stova)'),
    ];

    $form['isearch'] = [
      '#type' => 'fieldset',
      '#title' => t('iSearch'),
      '#collapsible' => FALSE,
    ];

    $form['isearch']['faculty_import_enabled'] = [
      '#type' => 'checkbox',
      '#title' => t('Enable iSearch importer'),
      '#description' => t('Tick to enable the automatic faculty & staff import from iSearch.'),
      '#default_value' => $this->config('tb_migrations.settings')
        ->get('faculty_import_enabled')
    ];

    $form['isearch']['faculty_import_ids'] = [
      '#type' => 'textarea',
      '#title' => t('iSearch asurite ids to import'),
      '#description' => t('Enter a comma-separated list of asurite ids. These will be imported along with all the TSGM-tagged bios on each import.'),
      '#default_value' => $this->config('tb_migrations.settings')
        ->get('faculty_import_ids')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $event_id = $form_state->getValue('event_id');
    if ($event_id != '' && !is_numeric($event_id)) {
      $form_state->setErrorByName('event_id', 'Invalid Events Ids format.');
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('tb_migrations.settings')
      ->set('events_updated_date', $form_state->getValue('events_updated_date'))
      ->set('faculty_import_enabled', $form_state->getValue('faculty_import_enabled'))
      ->set('faculty_import_ids', $form_state->getValue('faculty_import_ids'))
      ->save();
    
    $event_id = $form_state->getValue('event_id');
    if($event_id != '') {
      \Drupal::service('tb_migrations.sync_aventri')->sync($event_id);
    }

    parent::submitForm($form, $form_state);
  }

}
