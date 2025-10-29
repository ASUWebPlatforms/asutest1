<?php

namespace Drupal\tb_migrations;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelTrait;
use Drupal\node\Entity\Node;
use GuzzleHttp\Exception\RequestException;
use stdClass;

/**
 * Class AventriSync.
 */
class AventriSync {

  use LoggerChannelTrait;

  /**
   * The Aventry api base url.
   */
  const API_BASE_URL = 'https://api-na.eventscloud.com/api/v2';

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config_factory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructs Aventri sync service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->config_factory = $config_factory;
    $this->logger = $this->getLogger('tb_migrations');
  }

  /**
   * Execute Aventri synchronization.
   */
  public function sync($id = NULL) {
    $token_url = self::API_BASE_URL .  '/global/authorize.json?accountid=6377&key=2b4d6b282617f897b7054391086683e6d89db953';
    
    // Get token from above url.
    $token = FALSE;
    $client = \Drupal::httpClient();

    try {
      $request = $client->get($token_url);
      $file_contents = $request->getBody()->getContents();
      $file_contents = json_decode($file_contents);
      $token = $file_contents->accesstoken;
    }
    catch (RequestException $e) {
      // An error happened.
    }

    if ($token) {
      $ultimate_cron_entity = \Drupal::entityTypeManager()
        ->getStorage('ultimate_cron_job')
        ->load('tb_migrations_aventri');
      $log_entry = $ultimate_cron_entity->loadLatestLogEntry();
      $last_run = $log_entry->start_time ? \Drupal::service('date.formatter')->format((int) $log_entry->start_time, 'custom', 'Y-m-d') : $this->t('Never');

      if ($last_run == 'Never') {
        $last_run = '2021-10-23';
      }

      // Get the admin setting to import events updated since a certain date,
      // rather than since the last cron run.
      $config = $this->config_factory->get('tb_migrations.settings');
      $events_updated_date = $config->get('events_updated_date');
      if ($events_updated_date) {
        $last_run = $events_updated_date;
      }

      $fieldList = 'name,eventid,startdate,enddate,starttime,endtime,timezoneid,description,location,url,division,status';
      $list_events_endpoint = self::API_BASE_URL . '/global/listEvents.json?fields=' . $fieldList . '&search=code=Thunderbird,lastmodified>='. $last_run  . ',status=Live&accesstoken=' . $token;

      if ($id != NULL) {
        $list_events_endpoint = self::API_BASE_URL . '/global/listEvents.json?fields=' . $fieldList . '&search=code=Thunderbird,status=Live,eventid='. $id .'&accesstoken=' . $token;
      }

      // Get list of events from above endpoint.
      try {

        $request = $client->get($list_events_endpoint);
        $file_contents = $request->getBody()->getContents();
        $file_contents = json_decode($file_contents);

        foreach ($file_contents as $event) {
          $data = [];

          // An error message was returned instead of results.
          if (property_exists($event, 'data')) {
            $message = $event->data;
            $this->logger->info('Error returned from Aventri: ' . $message);
            continue;
          }

          $data['title'] = $event->name;
          $data['eventid'] = $event->eventid;
          $data['startdate'] = $event->startdate;
          $data['enddate'] = $event->enddate;
          $data['starttime'] = $event->starttime;
          $data['endtime'] = $event->endtime;
          $data['timezoneid'] = $event->timezoneid;
          $data['description'] = $event->description;
          $data['location'] = $event->location;
          $data['url'] = $event->url;
          $data['status'] = $event->status == 'Live' ? 1 : 0;

          // Get the event data and customfields 147847: Thunderbird: Degree custom field id.
          $event_data = $this->getEventData($event->eventid, $token);

          $cf_degree = $this->getEventCustomFieldValue('147847', $event_data->customfields);
          $data['degree'] = $cf_degree;

          $cf_division = $this->getEventCustomFieldValue('147848', $event_data->customfields);
          $data['division'] = $cf_division;
          
          // Sessions implementation
          $sessions_list = $this->getListSessions($event->eventid, $token);

          foreach ($sessions_list as $session) {
            if (property_exists($session, 'starttime')) {              
              $mapped_data = $this->getMappedSession($event, $session);
              $mapped_data['degree'] = $cf_degree;
              $mapped_data['timezoneid'] = $event->timezoneid;
              $mapped_data['division'] = $cf_division;
              $mapped_data['location'] = $event->location;
              $mapped_data['status'] = $data['status'];

              $this->createNode($mapped_data);
            }
          }
          
          // If the event has no sessions, it creates a node.
          if (empty($sessions_list)) {
            if ($data['title']) {
              $this->createNode($data);
            }
          }
        }
      }
      catch (RequestException $e) {
        // An error happened.
        $this->logger->error('Failed to get results from Aventri.');
      }

      // die(); // uncomment to see debugging
    }
  }

  /**
   * Create an Event node from Aventri data.
   *
   * @param array $data
   *   The data object.
   */
  protected function createNode($data) {
    // Code to create a node and save fields.

    // EVENT URL
    $eventUrl = 'https://na.eventscloud.com/' . $data['eventid'];
    if (isset($data['url']) &&  $data['url'] != '') {
      $eventUrl = 'https://na.eventscloud.com/' . $data['url'];
    }

    // START DATE
    $starttime = $data['starttime'];
    if ($starttime == '') {
      $starttime = '12:00:00';
    }

    // END DATE
    $endtime = $data['endtime'];
    if ($endtime == '') {
      $endtime = '12:00:00';
    }

    // List of the most commonly used timezones for events, keyed by ID used in
    // Aventri API. See: https://developer.aventri.com/#time-zone-list
    $timezones = [
      6 => 'America/Phoenix',
      14 => 'America/New_York',
      29 => 'Europe/Belgrade',
    ];

    $startdate = $data['startdate'] . 'T' . $starttime;
    $enddate = $data['enddate'] . 'T' . $endtime;

    // Check event_id  to see if node already exists.
    $query = \Drupal::entityQuery('node')
    ->condition('type', 'event')
    ->condition('field_aventri_id', $data['eventid'], '=')
    ->accessCheck(FALSE);
    
    // Check if it is a Aventri session.
    if (isset($data['sessionid'])) {
      $query->condition('field_aventri_session_id', $data['sessionid'], '=');
    }

    $query->range(0, 1);
    
    $results = $query->execute();
   
    if ($results) {
      $entity_type_manager = \Drupal::entityTypeManager();
      foreach ($results as $nid) {
        $storage = $entity_type_manager->getStorage('node');
        $node = $storage->load($nid);
        $node->set('status', $data['status'] == 1);
      }
    } else {
      $node = Node::create([
        'type' => 'event',
        'langcode' => 'en',
        'field_aventri_id' => $data['eventid'],
        'status' => $data['status'],
        'field_public' => 1,
        'field_event_source' => [
          'value' => 'aventri'
        ]
      ]);
    }

    if (isset($timezones[$data['timezoneid']])) {
      $timezone_id = $timezones[$data['timezoneid']];
      $node->set('field_timezone_id', $timezone_id);

      // Datetimes provided are in local time. Use provided timezone to convert
      // to UTC to store in database. Save the timezone ID to convert back, and
      // timezone abbreviation, for display on event cards.
      $startdate_new = new \DateTime($startdate, new \DateTimeZone($timezone_id));
      $timezone_abbrev = $startdate_new->format('T');
      $startdate_new->setTimezone(new \DateTimeZone('UTC'));
      $startdate = $startdate_new->format('Y-m-d\TH:i:s');
      $enddate_new = new \DateTime($enddate, new \DateTimeZone($timezone_id));
      $enddate_new->setTimezone(new \DateTimeZone('UTC'));
      $enddate = $enddate_new->format('Y-m-d\TH:i:s');

      $node->set('field_timezone', $timezone_abbrev);
    }

    $node->set('title', $data['title']);
    $node->set('body', $data['description']);
    $node->set('field_link', ['uri' => $eventUrl]);
    $node->set('field_event_date', $startdate);
    $node->set('field_end_date', $enddate);
    $node->set('field_event_location', $data['location']->name ?? '');
    $node->set('field_email', ['value' => $data['location']->email ?? '']);
    $node->set('field_phone_number', ['value' => $data['location']->phone ?? '']);
    $node->set('field_venue', ['value' => $data['location']->map ?? '']);

    if (isset($data['sessionid'])) {
      $node->set('field_aventri_session_id', $data['sessionid']);
    }
    
    // Assign event degree.
    if (isset($data['degree']) && $data['degree'] !== NULL && !empty($data['degree']->value) && $data['degree']->value !== 'Undecided') {

      $node->set('field_degree_code', $data['degree']->value);

      if ($degree_id = $this->getEventDegree($data['degree']->value)) {
        $node->set('field_degrees', ['target_id' => $degree_id]);
      }
    }

    // Assign event division.
    if (isset($data['division']) && $data['division'] !== NULL && !empty($data['division']->value) && $data['division']->value !== 'Undecided') {
      $results = \Drupal::entityTypeManager()->getStorage('taxonomy_term')
        ->loadByProperties([
          'vid' => 'event_categories',
          'name' => $data['division']->value
        ]);

      if (!empty($results)) {
        $term = reset($results);
        $node->set('field_event_categories', ['target_id' => $term->id()]);
      }
    }

    $node->save();
  }

  /**
   * Get an Event from Aventri used to be able to get custom fields data.
   *
   * @param int $event_id
   *   The Aventri event id.
   * @param string $access_token
   *  The access token.
   */
  protected function getEventData($event_id, $access_token) {
    $data = [];
    $client = \Drupal::httpClient();
    $get_event_endpoint = self::API_BASE_URL . '/ereg/getEvent.json?eventid=' . $event_id . '&accesstoken=' . $access_token . '&customfields=1';

    try {
      $request = $client->get($get_event_endpoint);
      $file_contents = $request->getBody()->getContents();
      $data = json_decode($file_contents);
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to get event data from Aventri.');
    }

    return $data;
  }

  /**
   * Get all sessions of event from Aventri.
   *
   * @param int $event_id
   *   The Aventri event id.
   * @param string $access_token
   *  The access token.
   */
  protected function getListSessions($event_id, $access_token) {
    $data = [];
    $client = \Drupal::httpClient();
    $get_list_sessions_endpoint = self::API_BASE_URL . '/ereg/listSessions.json?eventid=' . $event_id . '&accesstoken=' . $access_token . '&customfields=1';

    try {
      $request = $client->get($get_list_sessions_endpoint);
      $file_contents = $request->getBody()->getContents();
      $data = json_decode($file_contents);
    }
    catch (RequestException $e) {
      $this->logger->error('Failed to get list sessions from Aventri.');
    }

    if (is_object($data) && property_exists($data, 'error')) {
      $message = $data->error->data;
      $this->logger->info('Error returned from Aventri: ' . $message);
      $data = [];
    }

    return $data;
  }

  /**
   * Get the custom field value.
   *
   * @param string $field_id
   *   The custom field id.
   * @param array $custom_fields
   *  The custom fields list.
   */
  protected function getEventCustomFieldValue($field_id, $custom_fields) {

    $results = array_filter($custom_fields, function ($field) use ($field_id) {
      return $field->fieldid === $field_id;
    });

    return array_values($results)[0];
  }

  /**
   * Provides the session retrieved by Aventri mapped to be saved.
   *
   * @param array|object $session
   *  The Aventri session item response.
   * @param array|object $parent_event
   *  The Aventri session parent event item.
   */
  protected function getMappedSession($event, $session) {
    $data = [];

    $desceng = '';

    if (property_exists($session, 'choices_expanded')) {
      if (property_exists($session->choices_expanded, 'desceng')) {
        $desceng = $session->choices_expanded->desceng;
      }
    }

    $data['title'] = $session->name;
    $data['eventid'] = $session->eventid;
    $data['sessionid'] = $session->sessionid;
    $data['startdate'] = $session->sessiondate === '0000-00-00' ? $event->startdate : $session->sessiondate;
    $data['enddate'] = $session->sessiondate === '0000-00-00' ? $event->enddate : $session->sessiondate;
    $data['starttime'] = $session->starttime;
    $data['endtime'] = $session->endtime;
    $data['description'] = $desceng;

    return $data;
  }

  /**
   * Returns a degree nid based on the given degree code/name.
   *
   * @param string $degree_code
   *  The degree field value to lookup.
   */
  protected function getEventDegree($degree_code) {
    $results = \Drupal::entityTypeManager()->getStorage('node')
      ->loadByProperties([
        'type' => 'degree',
        'field_aventri_code' => $degree_code
      ]);

    if (!empty($results)) {
      $degree = reset($results);
      return $degree->id();
    }

    if (is_numeric($degree_code)) {
      return NULL;
    }

    // The Degree field received from Stova is inconsistent; sometimes the
    // degree name is given instead of the numeric degree code.
    // The degree names also don't always exactly match those in Drupal.

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'degree');

    if (strpos($degree_code, 'Space Leadership') !== FALSE) {
      $query->condition('title', '%Space Leadership%', 'LIKE');
    }
    else {
      $query->condition('title', str_replace('&', 'and', $degree_code), '=');
    }

    $results = $query->execute();

    if (!empty($results)) {
      $degree = reset($results);
      return $degree;
    }

    return NULL;
  }

}
