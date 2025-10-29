<?php

namespace Drupal\tb_migrations;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;

/**
 * Class IsearchSync.
 */
class IsearchSync {
  use LoggerChannelTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * The HTTP client.
   *
   * @var GuzzleHttp\Client
   */
  protected $client;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs faculty sync service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config_factory = $config_factory;
    $this->client = \Drupal::httpClient();
    $this->logger = $this->getLogger('tb_migrations');
  }

  /**
   * Execute iSearch synchronization.
   *
   * @param bool $all
   *   Whether to import all bios, or just specific asurite ids.
   */
  public function sync($all = TRUE) {
    // Get the admin setting to check whether the importer is enabled.
    $config = $this->config_factory->get('tb_migrations.settings');
    $enabled = $config->get('faculty_import_enabled');
    if (!$enabled) {
      return;
    }

    if ($all == TRUE) {
      $count = $this->importBios();
      $this->logger->info('Faculty import (TSGM department): Created ' . $count['new'] . ' new bios and updated ' . $count['updated'] . ' existing ones.<br>');
    }

    // Get admin list of asurite ids and run import again.
    $asurite_ids = $config->get('faculty_import_ids');
    if ($asurite_ids) {
      $count = $this->importBios(1, $asurite_ids);
      $this->logger->info('Faculty import (asurite ids): Created ' . $count['new'] . ' new bios and updated ' . $count['updated'] . ' existing ones.<br>');
    }

    // @TODO: If form has just been submitted, import bios immediately instead of queue.

    // die(); // uncomment to see debugging
  }

  /**
   * Retrieve the faculty bios from iSearch.
   *
   * @param int $page_no
   *   The page number to start from.
   * @param mixed $asurite_ids
   *   Optional list of asurite ids to filter by.
   * @param bool $recurse
   *   Whether to continue importing the next page of results.
   */
  protected function importBios($page_no = 1, $asurite_ids = FALSE, $count = ['new' => 0, 'updated' => 0], $recurse = TRUE) {
    // Code to create a node and save fields.

    $list_bios_endpoint = 'https://dev-asu-isearch.ws.asu.edu/api/v1/webdir-profiles/faculty-staff/filtered?query=&size=50&sort-by=last_name_asc&page=' . $page_no;

    if ($asurite_ids) {
      // If there's a list of ids, make sure spaces are removed then use to filter.
      $ids_array = explode(',', $asurite_ids);
      $ids_array = array_map('trim', $ids_array);
      $asurite_ids = implode(',', $ids_array);
      $list_bios_endpoint .= '&asurite_ids=' . $asurite_ids;
    } else {
      // If not filtering by id, instead filter by the Thunderbird department id.
      $list_bios_endpoint .= '&dept_ids=152057';
    }

    $results = FALSE;

    // Get list of bios from above endpoint.
    try {
      $request = $this->client->get($list_bios_endpoint);
      $status = $request->getStatusCode();
      $file_contents = $request->getBody()->getContents();

      $file_contents = json_decode($file_contents);

      $meta = $file_contents->meta;
      $results = $file_contents->results;
    }
    catch (RequestException $e) {
      // An error happened.
      $this->logger->error('Failed to get results from iSearch.');
    }

    if ($results) {
      foreach ($results as $bio) {
        $node_count = $this->createNode($bio);
        $count['new'] += $node_count['new'];
        $count['updated'] += $node_count['updated'];
      }

      if ($recurse) {
        // Check if we're on the last page of results. If not, continue to next page.
        $page_info = $meta->page;
        if ($page_no < $page_info->total_pages) {
          $page_no++;
          return $this->importBios($page_no, $asurite_ids, $count);
        }
      }
    }

    return $count;
  }

  /**
   * Create (or update) a faculty bio node from iSearch data.
   *
   * @param array $data
   *   The data object.
   */
  protected function createNode($bio) {
    // Code to create a node and save fields.
    $node_count = ['new' => 0, 'updated' => 0];
    $new = TRUE;

    $asurite_id = $bio->asurite_id->raw;
    $display_name = $bio->display_name->raw;

    // Check asurite id to see if node already exists.
    $query = \Drupal::entityQuery('node')
    ->condition('type', 'person_bio')
    ->condition('field_asu_rite_id', $asurite_id, '=')
    ->range(0, 1);

    $results = $query->execute();

    if ($results) {
      $entity_type_manager = \Drupal::entityTypeManager();
      foreach ($results as $nid) {
        $storage = $entity_type_manager->getStorage('node');
        $node = $storage->load($nid);
      }
      $new = FALSE;
    } else {
      $node = Node::create([
        'title' => $display_name,
        'type' => 'person_bio',
        'langcode' => 'en',
        'field_asu_rite_id' => $asurite_id,
        'field_imported' => 1,
        'status' => 0,
      ]);
    }

    // Check if node is locked - if so, don't update it.
    $locked = $node->field_locked->value;

    if (!$locked) {
      $this->setFields($node, $bio);
      $node->save();

      if ($new) {
        $node_count['new']++;
      } else {
        $node_count['updated']++;
      }
    }

    return $node_count;
  }

  /**
   * Save the bio fields from the provided data.
   *
   * @param object $node
   *   The node to save fields against.
   * @param array $bio
   *   The data object.
   */
  protected function setFields($node, $bio) {

    $field_mappings = [
      'eid' => 'field_employee_id',
      'first_name' => 'field_first_name',
      'middle_name' => 'field_middle_name',
      'last_name' => 'field_last_name',
      'email_address' => 'field_email',
      'phone' => 'field_phone_number',
      'fax' => 'field_fax',
      'twitter' => 'field_twitter',
      'linkedin' => 'field_linkedin',
      'facebook' => 'field_facebook',
      'bio' => ['field_bio', 'text_format' => 'basic_html'],
      'primary_title' => 'field_primary_title',
      'titles' => 'field_person_title',
      'expertise_areas' => ['field_expertise', 'vid' => 'expertise_areas'],
      'primary_search_department_affiliation' => ['field_departments', 'vid' => 'department'],
      'locations' => 'field_person_location',
      'city' => 'field_city',
      'primary_job_campus' => 'field_primary_job_campus',
      'primary_simplified_empl_class' => 'field_primary_employee_class',
      'simplified_empl_classes' => ['field_employee_types', 'vid' => 'employee_types'],
      'board' => ['field_board', 'text_format' => 'basic_html'],
      'education' => ['field_education', 'text_format' => 'basic_html'],
      'research_interests' => ['field_research_interests', 'text_format' => 'basic_html'],
      'editorships' => ['field_editorships', 'text_format' => 'basic_html'],
      'professional_associations' => ['field_professional_associations', 'text_format' => 'basic_html'],
      'grad_faculties' => ['field_grad_faculties', 'text_format' => 'basic_html'],
      'work_history' => ['field_work_history', 'text_format' => 'basic_html'],
      'industry_positions' => ['field_industry_positions', 'text_format' => 'basic_html'],      
    ];

    $taxonomy_manager = \Drupal::entityTypeManager()->getStorage('taxonomy_term');

    foreach ($field_mappings as $field_source => $field_info) {
      if (property_exists($bio, $field_source)) {
        $value = $bio->{$field_source}->raw;

        if (is_array($field_info)) {
          $field_name = reset($field_info);

          if ($node->hasField($field_name)) {
            // Long text fields containing HTML.
            if (isset($field_info['text_format'])) {
              // Filter HTML input to ensure it's safe.
              $value = Xss::filter($value, ['a', 'em', 'strong', 'h2', 'p', 'span', 'ul', 'ol', 'li']);
              $node->{$field_name}->setValue(['value' => $value, 'format' => $field_info['text_format']]);

            // Taxonomy fields with lists of values.
            } else if (isset($field_info['vid'])) {
              // If there are no values.
              if (!$value) {
                $value = [];
              }
              // Remove duplicate terms.
              $vid = $field_info['vid'];
              $value = array_unique($value);

              $term_ids = [];

              foreach ($value as $term_name) {
                // Attempt to load the term by name.
                $term = $taxonomy_manager->loadByProperties([
                  'name' => $term_name, 'vid' => $vid
                ]);
                $term = reset($term);

                // Create term if one doesn't already exist.
                if (!$term) {
                  $term = Term::create([
                    'name' => $term_name, 
                    'vid' => $vid,
                  ]);
                  $term->save();
                }

                $term_ids[] = $term->id();
              }

              $node->set($field_name, $term_ids);
            }
          }
        // Simple text fields.
        } else if ($node->hasField($field_info)) {
          // Don't import email address if it's the generic one.
          if ($field_info == 'field_email' && strtolower($value) == 'psnomail@asu.edu') {
            continue;
          }

          $node->set($field_info, $value);
        }
      }

    }

  }
}
