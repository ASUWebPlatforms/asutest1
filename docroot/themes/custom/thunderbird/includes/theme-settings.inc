<?php
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;

/**
 * Implements hook_form_system_theme_settings_alter(). thunderbird_theme_settings_form_system_theme_settings_alter
 */
function thunderbird_form_system_theme_settings_alter(&$form, FormStateInterface $form_state) {
    // Work-around for a core bug affecting admin themes. See issue #943212.
    if (isset($form_id)) {
        return;
    }

    $media_id = theme_get_setting('faculty_default_img', 'thunderbird');
    $media = $media_id ? Media::load($media_id) : NULL;

    $form['faculty_default_img'] = [
      '#type'          => 'entity_autocomplete',
      '#target_type'   => 'media',
      '#default_value' => $media,
      '#title' => t('Default Faculty Bio Image'),
    ];
}
