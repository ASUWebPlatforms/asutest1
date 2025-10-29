<?php

namespace Drupal\tb_gdmi\Services;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\group\Entity\GroupInterface;
use Drupal\webform\WebformInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 *  GDMI Assessment Calculation Service.
 */
class GdmiAssessmentCalculation {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The GDMI utils service.
   *
   * @var \Drupal\tb_gdmi\Services\GdmiUtils
   */
  protected $gdmiUtils;

  /**
   * Constructs the gdmi assessment calculation service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\tb_gdmi\Services\GdmiUtils $gdmi_utils
   *   The GDMI utils service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, GdmiUtils $gdmi_utils) {
    $this->entityTypeManager = $entity_type_manager;
    $this->gdmiUtils = $gdmi_utils;
  }

  /**
   * Calculates the results of a assessment submission.
   * 
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The submission entity.
   */
  function calculate(WebformSubmissionInterface $submission) {
    $webform = $submission->getWebform();
    $data = $submission->getData();
    
    // Group and sum values.
    $capitals_tree = [];
    foreach ($data as $key => $value) {
      $field_element = $webform->getElement($key);
      if (isset($field_element['#capital_taxonomy']) && is_numeric($value)) {
        /** @var \Drupal\taxonomy\Entity\Term $term */
        $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($field_element['#capital_taxonomy']);
        $term_key = $this->termNameToKey($term->getName());
        // Is subcategory
        if (!empty($term->parent->target_id)) { 
          $parent = $term->parent->entity;
          $parent_key = $this->termNameToKey($parent->getName());
          $capitals_tree[$parent_key]['items'][$term_key]['items'][$key] = $value;

          $sum = $capitals_tree[$parent_key]['items'][$term_key]['sum'] ?? 0; 
          $capitals_tree[$parent_key]['items'][$term_key]['sum'] =  $sum + $value;

        } else {
          $capitals_tree[$term_key]['items'][$key] = $value;
          $capitals_tree[$term_key]['sum'] += $value;
        }
      }
    }

    // Calculate averages
    foreach ($capitals_tree as &$capital) {
      if (isset($capital['items'])) {
        foreach ($capital['items'] as &$subcategory) {
          $subcategory['average'] = round($subcategory['sum'] / count($subcategory['items']), 1);
          unset($subcategory['sum']);

          $sum = $capital['sum'] ?? 0;
          $capital['sum'] = $sum + $subcategory['average'];
        }
      }

      $capital['average'] = round($capital['sum'] / count($capital['items']), 1);
      unset($capital['sum']);
    }

    return $capitals_tree;
  }

  /**
   * Calculates the groups average values.
   * 
   * @param \Drupal\group\Entity\GroupInterface $group
   *   The group entity.
   */
  function calculateGroup(GroupInterface $group) {

    $participants = $group->getMembers(['gdmi-participant']);
    
    $group_averages = [];
    $percentiles = [];
    $participants_completed = 0;

    // Process each group participant.
    foreach ($participants as $participant) {
      $participant_user = $participant->getUser();
      $access_code = $this->gdmiUtils->getUserAccessCodeByGroup($participant_user->field_gdmi_assessment_code, $group->id(), '1');
      if ($access_code !== NULL) {
        $participants_completed++;
        $results_data = json_decode($access_code->results, TRUE);
        $this->averagesGrupingArrays($results_data, $group_averages);
      }  
    }

    // Calculate averages percentiles.
    $percentiles = $this->calculateAveragesPercentiles($group_averages, $participants_completed);

    return [
      'means' => $group_averages,
      'percentiles' => $percentiles,
      'participants_completed' => $participants_completed,
      'participants_total' => count($participants),
      'date' => new \DateTime()
    ];
  }

  /**
   * Calculates the grand mean average values.
   * 
   * @param \Drupal\webform\WebformInterface $webform
   *   The webform entity.
   * @param bool $save
   *   Define if the calculation needs to be saved on db.
   */
  function calculateGrandMean(WebformInterface $webform, bool $save = TRUE) {

    $grand_averages = [];
    $percentiles = [];
    $groups_started = 0;

    // Select all groups of the same webform type.
    $results = \Drupal::database()->query("select co.order_id, fg.field_group_target_id as group_id, coi.purchased_entity as variation_id,
    cpvd.product_id, cpvd.title, cpaw.field_assessment_webform_target_id as webform 
    from commerce_order co inner join commerce_order__field_group fg 
    inner join commerce_order_item coi inner join commerce_product_variation_field_data cpvd 
    inner join commerce_product__field_assessment_webform cpaw 
    where co.state = 'completed' 
    and fg.entity_id = co.order_id 
    and coi.order_id = co.order_id 
    and cpvd.variation_id = coi.purchased_entity
    and cpaw.field_assessment_webform_target_id = '" . $webform->id() . "';")->fetchAll();

    // Process each group.
    foreach ($results as $item) {
      /** @var \Drupal\group\Entity\GroupInterface  $group */
      $group = $this->entityTypeManager->getStorage('group')->load($item->group_id);
      if ($group != null && $group->field_results_data->value) {
        $groups_started++;
        $group_data = json_decode($group->field_results_data->value, TRUE);
        $this->averagesGrupingArrays($group_data['means'], $grand_averages);
      }
    }

    // Calculate averages percentiles.
    $percentiles = $this->calculateAveragesPercentiles($grand_averages, $groups_started);

    $data = [
      'means' => $grand_averages,
      'percentiles' => $percentiles,
      'total_groups' => $groups_started,
      'date' => new \DateTime()
    ];

    if ($save) {
      $database = \Drupal::database();
      $exist = $database->query("select * from gdmi_grand_means where webform =  '" . $webform->id() . "';")->fetchAll();
      if (!empty($exist)) {
        $database->update('gdmi_grand_means')->fields(['results' => json_encode($data)])  ->condition('webform', $webform->id())->execute();
      } else {
        $database->insert('gdmi_grand_means')->fields(['webform' => $webform->id(), 'results' => json_encode($data)])->execute();
      }
    }

    return $data;
  }

  /**
   * Convert term string name to array key.
   * 
   * @param string $name
   *   The term name.
   */
  private function termNameToKey($name) {
    $name = trim($name);
    $name = str_replace(' ', '_', $name);
    $name = strtolower($name);
    return $name;
  }

  /**
   * Provide the array percentiles (25, 50, 75).
   * 
   * @param string $name
   *   The term name.
   */
  private function getArrayPercentiles($values) {
    sort($values);
    $percentiles = [];
    foreach ([25, 50, 75] as $percentile) {
      $index = ceil($percentile / 100 * count($values)) - 1;
      $percentiles[$percentile] = array_slice($values, 0, $index + 1);
    }
    return $percentiles;
  }

  /**
   * Group the capitals averages.
   * 
   * @param array|object $data
   *   The results data.
   */
  private function averagesGrupingArrays($data, &$averages) {
    foreach ($data as $capital_key => $capital) {
      if (isset($capital['items'])) {
        foreach ($capital['items'] as $subcategory_key => $subcategory) {
          $averages[$capital_key]['items'][$subcategory_key]['averages'][] = $subcategory['average'];
        }
      }
      $averages[$capital_key]['averages'][] = $capital['average'];
    }
  }

  /**
   * Calculates the capitals averages percentiles.
   * 
   * @param array|object $data
   *   The results data.
   * 
   * @param int $items_amount
   *   The total items amount.
   */
  private function calculateAveragesPercentiles(&$data, $items_amount) {
    $percentiles = [];
    foreach ($data as $capital_key  => &$group_mean) {
      if (isset($group_mean['items'])) {
        foreach ($group_mean['items'] as $subcategory_key => &$subcategory) {
          $sum = array_sum($subcategory['averages']);
          $averages_percentiles = $this->getArrayPercentiles($subcategory['averages']);
          $subcategory['average'] = round($sum / $items_amount, 1);
          $percentiles[$capital_key]['items'][$subcategory_key]['25'] = round(array_sum($averages_percentiles['25']) / count($averages_percentiles['25']), 1);
          $percentiles[$capital_key]['items'][$subcategory_key]['50'] = round(array_sum($averages_percentiles['50']) / count($averages_percentiles['50']), 1);          
          $percentiles[$capital_key]['items'][$subcategory_key]['75'] = round(array_sum($averages_percentiles['75']) / count($averages_percentiles['75']), 1);
          $percentiles[$capital_key]['items'][$subcategory_key]['min'] = min($subcategory['averages']);
          $percentiles[$capital_key]['items'][$subcategory_key]['max'] = max($subcategory['averages']);
          unset($subcategory['averages']);
        }
      }

      $sum = array_sum($group_mean['averages']);
      $averages_percentiles = $this->getArrayPercentiles($group_mean['averages']);
      $group_mean['average'] = round($sum / $items_amount, 1);
      $percentiles[$capital_key]['25'] = round(array_sum($averages_percentiles['25']) / count($averages_percentiles['25']), 1);
      $percentiles[$capital_key]['50'] = round(array_sum($averages_percentiles['50']) / count($averages_percentiles['50']), 1);          
      $percentiles[$capital_key]['75'] = round(array_sum($averages_percentiles['75']) / count($averages_percentiles['75']), 1);
      $percentiles[$capital_key]['min'] = min($group_mean['averages']);
      $percentiles[$capital_key]['max'] = max($group_mean['averages']);
      unset($group_mean['averages']);
    }
    return $percentiles;
  }
}
