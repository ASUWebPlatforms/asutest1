<?php

namespace Drupal\tb_webform;

use Drupal\Core\Url;
use Drupal\webform\WebformSubmissionForm;

/**
 * Extension of webform submission form, to alter redirect URL.
 */
class TbWebformSubmissionForm extends WebformSubmissionForm {

  /**
   * {@inheritdoc}
   */
  protected function getConfirmationUrl() {
    $confirmation_url = trim($this->getWebformSetting('confirmation_url', ''));

    $parsed_confirmation_url = parse_url($confirmation_url);

    if (!isset($parsed_confirmation_url['query']))
      return Url::fromUri($this->getRequest()->getSchemeAndHttpHost() . $confirmation_url);

    parse_str($parsed_confirmation_url['query'], $query);

    foreach($query as $key=>$value) {
      if (is_null($value) || $value == '') {
        unset($query[$key]);
      }
      else if (strpos($value, 'webform_submission') !== false) {
        unset($query[$key]);
      }
      // The options for these questions each have multiple values which need to
      // be passed to the results view filters.
      else if (in_array($key, ['years_of_experience', 'delivery_method'])) {
        $values_array = explode(',', $value);
        $query[$key] = $values_array;
      }
    }

    $confirmation_url = $parsed_confirmation_url['path'] . '?' . http_build_query($query);

    if (strpos($confirmation_url, '/') === 0) {
      // Get redirect URL using an absolute URL for the absolute  path.
      $redirect_url = Url::fromUri($this->getRequest()->getSchemeAndHttpHost() . $confirmation_url);
    }
    elseif (preg_match('#^[a-z]+(?:://|:)#', $confirmation_url)) {
      // Get redirect URL from URI (i.e. http://, https:// or ftp://)
      // and Drupal custom URIs (i.e internal:).
      $redirect_url = Url::fromUri($confirmation_url);
    }
    elseif (strpos($confirmation_url, '<') === 0) {
      // Get redirect URL from special paths: '<front>' and '<none>'.
      $redirect_url = $this->pathValidator->getUrlIfValid($confirmation_url);
    }
    else {
      // Get redirect URL by validating the Drupal relative path which does not
      // begin with a forward slash (/).
      $confirmation_url = $this->aliasManager->getPathByAlias('/' . $confirmation_url);
      $redirect_url = $this->pathValidator->getUrlIfValid($confirmation_url);
    }

    // If redirect url is FALSE, display and log a warning.
    if (!$redirect_url) {
      $webform = $this->getWebform();
      $t_args = [
        '@webform' => $webform->label(),
        '%url' => $this->getWebformSetting('confirmation_url'),
      ];
      // Display warning to use who can update the webform.
      if ($webform->access('update')) {
        $this->messenger()->addWarning($this->t('Confirmation URL %url is not valid.', $t_args));
      }
      // Log warning.
      $this->getLogger('webform')->warning('@webform: Confirmation URL %url is not valid.', $t_args);
    }

    return $redirect_url;
  }

}
