/**
 * @file
 * Sets up the summary for User Assessment Available on vertical tabs of block forms.
 */
 
(function ($, Drupal) {
 
    'use strict';
   
    function checkboxesSummary(context) {
      var conditionChecked = $(context).find('[data-drupal-selector="edit-visibility-user-assessment-available-show"]:checked').length;
      var negateChecked = $(context).find('[data-drupal-selector="edit-visibility-user-assessment-available-negate"]:checked').length;
   
      if (conditionChecked) {
        if (negateChecked) {
          // Both boxes have been checked.
          return Drupal.t("Won't be shown if the user has an assessment currently available to start");
        }
   
        // The condition has been enabled.
        return Drupal.t("Shown if the user has an assessment currently available to start");
      }
   
      // The condition has not been enabled and is not negated.
      return Drupal.t('Not Restricted');
    }
   
    /**
     * Provide the summary information for the block settings vertical tabs.
     *
     * @type {Drupal~behavior}
     *
     * @prop {Drupal~behaviorAttach} attach
     *   Attaches the behavior for the block settings summaries.
     */
    Drupal.behaviors.blockSettingsSummaryUserAssessmentAvailable = {
      attach: function () {
        // Only do something if the function drupalSetSummary is defined.
        if (jQuery.fn.drupalSetSummary !== undefined) {
          // Set the summary on the vertical tab.
          $('[data-drupal-selector="edit-visibility-user-assessment-available"]').drupalSetSummary(checkboxesSummary);
        }
      }
    };
   
  }(jQuery, Drupal));