<?php

namespace Drupal\editoria11y;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Component\Utility\Xss;

/**
 * Handles database calls for DashboardController.
 */
class Dashboard {
  /**
   * Database property.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * Constructs a dashboard object.
   *
   * @param Drupal\Core\Database\Connection $database
   *   Database property.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Returns list of content languages present in results.
   *
   * @return array
   *   Return languages array.
   */
  public function getLanguages(): array {
    $langs = $this->database->select('editoria11y_results')
      ->fields('editoria11y_results', ['page_language'])
      ->groupBy('page_language');
    $count = $langs->countQuery()->execute()->fetchField();
    $languages = $langs->execute();
    return ["languages" => $languages, "count" => $count];
  }

  /**
   * Gets list of entity types.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Return the entity types.
   */
  public function getEntityTypes(): ?StatementInterface {

    $query = $this->database->select('editoria11y_results', 't')
      ->fields('t', ['entity_type'])
      ->groupBy('entity_type')
      ->orderBy('entity_type')
      ->execute();

    return $query;
  }

  /**
   * Gets unique route names for filter selects.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Returns the statementInterface property.
   */
  public function getResultNames(): ?StatementInterface {

    $query = $this->database->select('editoria11y_dismissals', 't')
      ->fields('t', ['result_name'])
      ->groupBy('result_name')
      ->orderBy('result_name')
      ->execute();

    return $query;
  }

  /**
   * Gets unique route names for filter selects.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Return the null option
   */
  public function getUserIds(): ?StatementInterface {

    $query = $this->database->select('editoria11y_dismissals', 't')
      ->fields('t', ['uid'])
      ->groupBy('uid')
      ->orderBy('uid')
      ->execute();
    return $query;
  }

  /**
   * Returns a sortable list of alerts and a count of the total.
   *
   * @param int $page
   *   Page variable.
   * @param string $header
   *   Header variable.
   * @param int $range
   *   Range variable.
   * @param mixed $form_title
   *   Form title variable.
   * @param mixed $form_url
   *   Form url variable.
   * @param mixed $form_filters
   *   Form filters variable.
   *
   * @return array
   *   Returns the results.
   */
  public function getResults(int $page = 0, $header = ['page_title'], $range = 50, $form_title = FALSE, $form_url = FALSE, $form_filters = []): array {
    $type = \Drupal::request()->query->get('type');
    $lang = \Drupal::request()->query->get('lang');

    $query = $this->database->select('editoria11y_results', 't')
      ->extend('Drupal\\Core\\Database\\Query\\TableSortExtender');
    $query->fields('t', [
      'page_path',
      'page_title',
      'page_result_count',
      'entity_type',
      'page_language',
    ])
        // We have to group by all fields due to sql_mode=only_full_group_by:
        // "SELECT list is not in GROUP BY clause and contains nonaggregated
        // column".
      ->groupBy('page_path')
      ->groupBy('page_title')
      ->groupBy('entity_type')
      ->groupBy('page_language')
      ->groupBy('page_result_count');
    if ($form_title) {
      $query->condition('page_title', '%' . $this->database->escapeLike($form_title) . '%', 'LIKE');
    }
    if ($form_url) {
      $query->condition('page_path', '%' . $this->database->escapeLike($form_url) . '%', 'LIKE');
    }
    foreach ($form_filters as $key => $value) {
      if ($value && $value !== "all") {
        $query->condition($key, $value);
      }
    }
    $query->orderByHeader($header);
    if ($range !== "all") {
      $num_per_page = $range;
      $offset = $num_per_page * $page;
      // Add table sort extender.
      $query->range($offset, $offset + $num_per_page);
    }
    $total = $this->database->select('editoria11y_results')
      ->fields('editoria11y_results', ['page_path'])
      ->groupBy('page_path');
    $count = $total->countQuery()->execute()->fetchField();

    $results = $query
      ->execute();

    return ["results" => $results, "count" => $count];
  }

  /**
   * Function to get the results in the top.
   */
  public function getTopResults(): StatementInterface {
    $lang = \Drupal::request()->query->get('lang');
    $query = $this->database->select('editoria11y_results');
    $query->fields('editoria11y_results', [
      'page_result_count',
      'page_path',
      'page_title',
      'page_language',
    ])
      ->groupBy('page_path')
      ->groupBy('page_title')
      ->groupBy('page_language')
      ->groupBy('page_result_count');
    if (!empty($lang) && $lang !== 'all') {
      $query->condition('page_language', $lang);
    }
    $query
      ->orderBy('page_result_count', 'desc')
      ->orderBy('page_title', 'asc')
      ->range(0, 5);

    return $query->execute();
  }

  /**
   * Returns list of pages with a count of the alerts on each page.
   *
   * @param int $page
   *   Page variable.
   * @param string $header
   *   Page variable.
   * @param int $range
   *   Range variable.
   * @param mixed $form_title
   *   Form title variable.
   * @param mixed $form_url
   *   Form url variable.
   * @param mixed $form_filters
   *   Form filters variable.
   *
   * @return array
   *   Return the recent results.
   */
  public function getRecent(int $page = 0, $header = ['page_title'], $range = 50, $form_title = FALSE, $form_url = FALSE, $form_filters = []): array {
    $type = \Drupal::request()->query->get('type');
    $lang = \Drupal::request()->query->get('lang');

    $query = $this->database->select('editoria11y_results', 't')
      ->extend('Drupal\\Core\\Database\\Query\\TableSortExtender');
    $query->fields('t', [
      'page_path',
      'result_name',
      'created',
      'page_title',
      'result_name_count',
      'entity_type',
      'page_language',
    ])
        // We have to group by all fields due to sql_mode=only_full_group_by:
        // "SELECT list is not in GROUP BY clause and contains nonaggregated
        // column".
      ->groupBy('page_path')
      ->groupBy('result_name')
      ->groupBy('page_title')
      ->groupBy('entity_type')
      ->groupBy('page_language')
      ->groupBy('result_name_count')
      ->groupBy('created');
    if ($form_title) {
      $query->condition('page_title', '%' . $this->database->escapeLike($form_title) . '%', 'LIKE');
    }
    if ($form_url) {
      $query->condition('page_path', '%' . $this->database->escapeLike($form_url) . '%', 'LIKE');
    }
    foreach ($form_filters as $key => $value) {
      if ($value && $value !== "all") {
        $query->condition($key, $value);
      }
    }
    $query->orderByHeader($header);
    if ($range !== "all") {
      $num_per_page = $range;
      $offset = $num_per_page * $page;
      // Add table sort extender.
      $query->range($offset, $offset + $num_per_page);
    }
    $total = $this->database->select('editoria11y_results')
      ->fields('editoria11y_results', ['page_path'])
      ->groupBy('page_path');
    $count = $total->countQuery()->execute()->fetchField();

    $results = $query
      ->execute();

    return ["results" => $results, "count" => $count];
  }

  /**
   * Gets pages with a given issue.
   *
   * @return array
   *   Returns the results by issue.
   */
  public function getResultsByIssue($resultKey, $page, $header): array {

    $num_per_page = 50;
    $offset = $num_per_page * $page;

    $query = $this->database->select('editoria11y_results', 't')
      ->extend('Drupal\\Core\\Database\\Query\\TableSortExtender');
    $query->fields('t', [
      'page_title',
      'route_name',
      'page_path',
      'result_name_count',
    ])
      ->groupBy('page_title')
      ->groupBy('route_name')
      ->groupBy('page_path')
      ->groupBy('result_name_count')
      ->condition('result_name', $resultKey)
      ->orderByHeader($header);
    // Heeeere.
    $count = $query->countQuery()->execute()->fetchField();
    $results = $query->range($offset, $offset + $num_per_page)->execute();

    return ["results" => $results, "count" => $count];
  }

  /**
   * Given a path, returns issues found on page.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Returns the results by page.
   */
  public function getResultsByPage($resultKey, $header): ?StatementInterface {
    $query = $this->database->select('editoria11y_results', 't')
      ->extend('Drupal\\Core\\Database\\Query\\TableSortExtender');
    $query->fields('t', [
      'page_title',
      'route_name',
      'page_path',
      'result_name_count',
      'result_name',
    ])
      ->condition('page_path', $resultKey)
      ->orderByHeader($header);

    return $query->execute();
  }

  /**
   * Gets types of issues found, and a count of number of pages with issue.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   Returns the get Issue list.
   */
  public function getIssueList(int $count = 0, $language = 'all'): ?StatementInterface {
    $query = $this->database->select('editoria11y_results');
    $query->fields('editoria11y_results', ['result_name'])
      ->groupBy('result_name')
      ->addExpression('sum(result_name_count)', 'result_name_count');
    if ($language !== 'all') {
      $query->condition('page_language', $language);
    }
    $query
      ->orderBy('result_name_count', 'desc')
      ->orderBy('result_name', 'asc');
    if ($count > 0) {
      $query->range(0, $count);
    }

    return $query->execute();
  }

  /**
   * Gets list of most recent "mark as OK" or "hide alert" actions.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   Returns the Recent dismissals.
   */
  public function getRecentDismissals(): StatementInterface {
    $query = $this->database->select('editoria11y_dismissals')
      ->fields('editoria11y_dismissals', [
        'page_title',
        'page_path',
        'created',
        'result_name',
        'dismissal_status',
        'uid',
      ])
      ->condition('stale', '0')
      ->orderBy('created', 'DESC');

    $results = $query->range(0, 5);

    return $results->execute();
  }

  /**
   * Gets all "mark as OK" or "hide alert" actions.
   *
   * @return array
   *   Return dismissals.
   */
  public function getDismissals($page, $header, $form_title, $form_url, $form_filters): array {
    $num_per_page = 50;
    $offset = $num_per_page * $page;
    // Hardcoding stale exclusion until filters and deletions are available.
    // @todo stale maintenance.
    $query = $this->database->select('editoria11y_dismissals', 't')
      ->extend('Drupal\\Core\\Database\\Query\\TableSortExtender');
    $query->fields('t', [
      'page_title',
      'route_name',
      'page_path',
      'result_name',
      'page_language',
      'dismissal_status',
      'uid',
      'created',
      'stale',
    ])
      ->groupBy('page_title')
      ->groupBy('route_name')
      ->groupBy('page_path')
      ->groupBy('result_name')
      ->groupBy('page_language')
      ->groupBy('dismissal_status')
      ->groupBy('uid')
      ->groupBy('created')
      ->groupBy('stale');
    foreach ($form_filters as $key => $value) {
      if ($value && $value !== "all") {
        $query->condition($key, $value, '=');
        // $query->condition($key, '%' . $this->database->escapeLike($value),
        // 'LIKE');
      }
    }

    if ($form_title) {
      $query->condition('page_title', '%' . $this->database->escapeLike($form_title) . '%', 'LIKE');
    }
    if ($form_url) {
      $query->condition('page_path', '%' . $this->database->escapeLike($form_url) . '%', 'LIKE');
    }
    $query
      ->orderByHeader($header);

    $count = $query->countQuery()->execute()->fetchField();
    $results = $query->range($offset, $offset + $num_per_page)->execute();

    return ["results" => $results, "count" => $count];
  }

  /**
   * Function to get the page language.
   */
  public function getPageLang() {
    $language = \Drupal::request()->query->get('lang');
    if (!is_null($language)) {
      $lang = Xss::filter($language);
    }
    else {
      $lang = \Drupal::languageManager()->getCurrentLanguage()->getId();
    }
    return $lang;
  }

  /**
   * Builds list of links to show results for specific languages.
   *
   * @return string
   *   Returns the string to language filters function
   */
  public function getLanguageFilters($lang = NULL) {
    $lang = \Drupal::request()->query->get('lang');
    $languages = $this->getLanguages();
    $links = '';
    if ($languages['count'] > 1) {
      $links .= "<nav aria-label='" . t('Language') . "'>" . t('Language:');
      $active = [
        // Increase target size.
        'attributes' => [
          'aria-current' => [
            'page',
          ],
        ],
      ];
      if ($lang === "all" || !$lang) {
        $links .= "<strong>";
        $linkString = Url::fromRoute('<current>', ['lang' => 'all'], $active);
        $linkToLang = Link::fromTextAndUrl('all', $linkString)->toString();
        $links .= $linkToLang . "</strong>";
      }
      else {
        $linkString = Url::fromRoute('<current>', ['lang' => 'all']);
        $linkToLang = Link::fromTextAndUrl('all', $linkString)->toString();
        $links .= $linkToLang;
      }
      foreach ($languages['languages'] as $id) {
        if ($id->page_language === $lang) {
          $links .= " | <strong>";
          $linkString = Url::fromRoute('<current>', ['lang' => $id->page_language], $active);
          $linkToLang = Link::fromTextAndUrl($id->page_language, $linkString)->toString();
          $links .= $linkToLang . "</strong>";
        }
        else {
          $links .= " | ";
          $linkString = Url::fromRoute('<current>', ['lang' => $id->page_language]);
          $linkToLang = Link::fromTextAndUrl($id->page_language, $linkString)->toString();
          $links .= $linkToLang;
        }
      }
    }
    $links .= "</nav>";
    return $links;
  }

  /**
   * ExportPages function.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   Returns all results as individual rows.
   */
  public function exportPages(): StatementInterface {

    $query = $this->database->select('editoria11y_results', 't');
    $query->fields('t', [
      'page_path',
      'page_title',
      'page_result_count',
      'entity_type',
      'page_language',
    ]);
    $query
      ->groupBy('page_path')
      ->groupBy('page_title')
      ->groupBy('page_result_count')
      ->groupBy('entity_type')
      ->groupBy('page_language');
    $query->orderBy('page_result_count', 'DESC');
    $query->orderBy('page_path', 'ASC');

    $results = $query
      ->execute();

    return $results;
  }

  /**
   * Function to export the issues.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   Returns all results as individual rows
   */
  public function exportIssues(): StatementInterface {

    $query = $this->database->select('editoria11y_results', 't');
    $query->fields('t', [
      'result_name',
      'page_path',
      'page_title',
      'entity_type',
      'page_language',
    ]);
    $query->orderBy('result_name', 'ASC');
    $query->orderBy('page_path', 'ASC');
    $results = $query
      ->execute();

    return $results;
  }

  /**
   * Export dismissals function.
   *
   * @return \Drupal\Core\Database\StatementInterface
   *   Returns all results as individual rows.
   */
  public function exportDismissals(): StatementInterface {

    $query = $this->database->select('editoria11y_dismissals', 't')
      ->extend('Drupal\\Core\\Database\\Query\\TableSortExtender');
    $query->fields('t', ['
    page_title',
      'route_name',
      'page_path',
      'result_name',
      'page_language',
      'dismissal_status',
      'uid',
      'created',
      'stale',
    ])
      ->orderBy('page_path')
      ->orderBy('result_name');

    $results = $query->execute();

    return $results;
  }

}
