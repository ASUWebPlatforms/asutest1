<?php

namespace Drupal\editoria11y\Controller;

use Drupal\Component\Utility\Xss;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\user\Entity\User;
use Drupal\Core\Form\FormState;

/**
 * Provides route responses for the Editoria11y module.
 */
class DashboardController extends ControllerBase {
  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The current database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Dashboard property.
   *
   * @var \Drupal\editoria11y\Dashboard
   */
  protected $dashboard;

  /**
   * Constructs a DashboardController object.
   *
   * @param string $dashboard
   *   Dashboard property.
   */
  public function __construct($dashboard) {
    $this->dashboard = $dashboard;
  }

  /**
   * {@inheritdoc}
   */
  public static function create($container) {
    return new static(
          $container->get('editoria11y.dashboard')
      );
  }

  /**
   * Throws error if sync is disabled.
   */
  private function syncStatus() {
    $config = \Drupal::config('editoria11y.settings');
    // Force standard bool.
    $sync = $config->get('disable_sync');
    if (!!$sync) {
      $msg = t("Dashboard sync is disabled in Editoria11y configuration.");
      \Drupal::messenger()->addWarning($msg, $type = 'warning');
    }
  }

  /**
   * Get lang from URL query or Drupal native code.
   *
   * @return string
   * Returns the respective string.
   */

  /**
   * Gets list of pages with accessibility issues.
   *
   * @return array
   *   Returns the respective array.
   */
  public function getTestResults() {
    $pager_manager = \Drupal::service('pager.manager');
    $page = $pager_manager
      ->findPage();

    // Load filters.
    $form_state = new FormState();
    $form_state->setRebuild();
    $form = \Drupal::formBuilder()->buildForm('Drupal\editoria11y\Form\DashboardFilters', $form_state);
    $form_title = $form_state->getValue('title');
    $form_url = $form_state->getValue('url');
    $form_filters = [
      'page_language' => $form_state->getValue('lang'),
      'entity_type' => $form_state->getValue('type'),
      'result_name' => $form_state->getValue('result'),
    ];

    $header = [
      ['data' => $this->t("Page"), 'field' => 't.page_title'],
      [
        'data' => $this->t("Issues Found"),
        'field' => 't.page_result_count',
        'sort' => 'desc',
      ],
      ['data' => $this->t("Type"), 'field' => 't.entity_type'],
      ['data' => $this->t("Path"), 'field' => 't.page_path'],
    ];
    $rows = [];

    $results = $this->dashboard->getResults($page, $header, 50, $form_title, $form_url, $form_filters);
    foreach ($results["results"] as $record) {
      // To review: https://drupal.stackexchange.com/questions/144992/how-do-i-create-a-link
      $url = UrlHelper::filterBadProtocol($record->page_path);
      $url .= strpos($url, '?') ? '&ed1ref=' . urlencode($url) : '?ed1ref=' . urlencode($url);
      $linkToPage = [
        'data' => [
          '#markup' => "<a href='" . $url . "'>" . $record->page_title . "</a>",
        ],
      ];
      $issuesByPage = Url::fromUserInput("/admin/reports/editoria11y/page?q=" . $record->page_path);
      $linkToIssuesByPage = Link::fromTextAndUrl($record->page_result_count, $issuesByPage)->toString();
      $rows[] = [$linkToPage,
        $linkToIssuesByPage,
        $record->entity_type,
        $record->page_path,
      ];
    }

    $render = [];
    $render['form'] = [$form];

    $render[] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => [
        'contexts' => [
          'user',
          'url',
        ],
        'tags' => [
          'editoria11y.dashboard',
        ],
      ],
    ];
    $pager_manager
      ->createPager($results["count"], 50);
    $render[] = [
      '#type' => 'pager',
      '#suffix' => "<p>&nbsp;</p>",
    ];

    return $render;
  }

  /**
   * Gets most recent issues.
   *
   * @return array
   *   Return the recent issues array.
   */
  public function getRecentIssues() {
    $pager_manager = \Drupal::service('pager.manager');
    $page = $pager_manager
      ->findPage();

    // Load filters.
    $form_state = new FormState();
    $form_state->setRebuild();
    $form = \Drupal::formBuilder()->buildForm('Drupal\editoria11y\Form\DashboardFilters', $form_state);
    $form_title = $form_state->getValue('title');
    $form_url = $form_state->getValue('url');
    $form_filters = [
      'page_language' => $form_state->getValue('lang'),
      'entity_type' => $form_state->getValue('type'),
      'result_name' => $form_state->getValue('result'),
    ];

    $header = [
        [
          'data' => $this->t("Detected"),
          'field' => 't.created',
          'sort' => 'desc',
        ],
        ['data' => $this->t("Issue"), 'field' => 't.result_name'],
        ['data' => $this->t("Count"), 'field' => 't.result_name_count'],
        ['data' => $this->t("Page"), 'field' => 't.page_title'],
        ['data' => $this->t("Path"), 'field' => 't.page_path'],
        ['data' => $this->t("Type"), 'field' => 't.entity_type'],

    ];
    $rows = [];

    $results = $this->dashboard->getRecent($page, $header, 50, $form_title, $form_url, $form_filters);
    foreach ($results["results"] as $record) {
      // To review: https://drupal.stackexchange.com/questions/144992/how-do-i-create-a-link
      $url = UrlHelper::filterBadProtocol($record->page_path);
      $url .= strpos($url, '?') ? '&ed1ref=' . urlencode($url) : '?ed1ref=' . urlencode($url);
      $linkToPage = [
        'data' => [
          '#markup' => "<a href='" . $url . "'>" . $record->page_title . "</a>",
        ],
      ];
      $date = \Drupal::service('date.formatter')->format($record->created, 'short');
      $issuesByPage = Url::fromUserInput("/admin/reports/editoria11y/page?q=" . $record->page_path);
      $linkToIssuesByPage = Link::fromTextAndUrl($record->result_name_count, $issuesByPage)->toString();
      $pages_by_issue_url = Url::fromUserInput("/admin/reports/editoria11y/issue?q=" . $record->result_name);
      $pages_by_issue = Link::fromTextAndUrl($record->result_name, $pages_by_issue_url)->toString();
      $rows[] = [$date, $pages_by_issue, $linkToIssuesByPage, $linkToPage,
        $record->page_path,
        $record->entity_type,
      ];
    }

    $render = [];
    $render['form'] = [$form];

    $render[] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#cache' => [
        'contexts' => [
          'user',
          'url',
        ],
        'tags' => [
          'editoria11y.dashboard',
        ],
      ],
    ];
    $pager_manager
      ->createPager($results["count"], 50);
    $render[] = [
      '#type' => 'pager',
      '#suffix' => "<p>&nbsp;</p>",
    ];

    return $render;
  }

  /**
   * Given a query variable of a page id, gets issues on page.
   *
   * @return array
   *   Returns the issues by page.
   */
  public function getIssuesByPage() {
    $header = [
        [
          'data' => $this->t("Count"),
          'field' => 't.result_name_count',
          'sort' => 'desc',
        ],
        ['data' => $this->t("Issue"), 'field' => 't.result_name'],
    ];

    $rows = [];

    $key = \Drupal::request()->query->get('q');
    if (!is_null($key)) {
      $key = Xss::filter($key);
    }

    $results = $this->dashboard->getResultsByPage($key, $header);
    $page = FALSE;

    foreach ($results as $record) {
      $url = UrlHelper::filterBadProtocol($record->page_path);
      $url .= strpos($url, '?') ? '&ed1ref=' . urlencode($url) : '?ed1ref=' . urlencode($url);
      $page = "<a href='" . $url . "'>" . $record->page_title . "</a>";
      $pages_by_issue_url = Url::fromUserInput("/admin/reports/editoria11y/issue?q=" . $record->result_name);
      $pages_by_issue = Link::fromTextAndUrl($record->result_name, $pages_by_issue_url)->toString();
      $rows[] = [$record->result_name_count, $pages_by_issue];
    }

    if (\Drupal::currentUser()->hasPermission('administer editoria11y checker')) {
      $purge = "<a href='#ed11y-purge' class='ed11y-reset-page action-link action-link--danger action-link--icon-trash' data-target='page'> " . t("Reset results for this page") . "</a>";
    }
    else {
      $purge = "";
    }
    if ($page) {
      $page_link = "<h2 id='ed11y-page'>" . t('Page:') . ' ' . $page . "</h2>";

      return [
        '#type' => 'container',
        'page_title' => [
          'element' => [
            '#markup' => $page_link,
          ],
        ],

        'content' => [
          '#type' => 'table',
          '#header' => $header,
          '#rows' => $rows,
          '#cache' => [
            'contexts' => [
              'user',
              'url',
            ],
            'tags' => [
              'editoria11y.dashboard',
            ],
          ],
        ],
        'purger' => [
          'element' => [
            '#markup' => $purge,
          ],
        ],
      ];

    }
    else {
      return [
        '#type' => 'container',
        'page_title' => [
          'element' => [
            '#markup' => "<h2>" . t("No known issues at this URL") . "</h2>",
          ],
        ],
        '#cache' => [
          'contexts' => [
            'user',
            'url',
          ],
          'tags' => [
            'editoria11y.dashboard',
          ],
        ],
      ];
    }
  }

  /**
   * Given a query variable of an issue name, builds table of issues.
   *
   * @return array
   *   Returns the pages by issue.
   */
  public function getPagesByIssue() {

    $pager_manager = \Drupal::service('pager.manager');
    $page = $pager_manager->findPage();

    $header = [
        [
          'data' => $this->t("Count"),
          'field' => 't.result_name_count',
          'sort' => 'desc',
        ],
        ['data' => $this->t("Page"), 'field' => 't.page_title'],
        ['data' => $this->t("Url"), 'field' => 't.page_path'],
    ];

    $rows = [];
    $key = \Drupal::request()->query->get('q');
    $results = $this->dashboard->getResultsByIssue($key, $page, $header);

    foreach ($results["results"] as $record) {
      $url = UrlHelper::filterBadProtocol($record->page_path);
      $url .= strpos($url, '?') ? '&ed1ref=' . urlencode($url) : '?ed1ref=' . urlencode($url);
      $linkToPage = [
        'data' => [
          '#markup' => "<a href='" . $url . "'>" . $record->page_title . "</a>",
        ],
      ];
      $rows[] = [$record->result_name_count, $linkToPage, $record->page_path];
    }

    $pager_manager
      ->createPager($results["count"], 50);

    $render = [];
    $render[] = [
      '#type' => 'container',
      'page_title' => [
        'element' => [
          '#markup' => "<h2>" . t('Pages with " @key " errors', ['@key' => $key]) . "</h2>",
        ],
      ],
      'content' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#cache' => [
          'contexts' => [
            'user',
            'url',
          ],
          'tags' => [
            'editoria11y.dashboard',
          ],
        ],
      ],
    ];
    $render[] = [
      '#type' => 'pager',
    ];
    return $render;
  }

  /**
   * Get all the top pages.
   */
  private function getTopPages() {
    $header = [
      t("Page"),
      t("Count"),
    ];
    $rows = [];
    $results = $this->dashboard->getTopResults();
    foreach ($results as $record) {
      $url = UrlHelper::filterBadProtocol($record->page_path);
      $url .= strpos($url, '?') ? '&ed1ref=' . urlencode($url) : '?ed1ref=' . urlencode($url);
      $linkToPage = [
        'data' => [
          '#markup' => "<a href='" . $url . "'>" . $record->page_title . "</a>",
        ],
      ];
      $link_options = [
          // Increase target size.
        'attributes' => [
          'class' => [
            'action-link--small',
          ],
        ],
      ];
      $linkString = Url::fromRoute('editoria11y.reports_issues_by_page', ['q' => $record->page_path], $link_options);
      $linkToIssueList = Link::fromTextAndUrl($record->page_result_count, $linkString)->toString();
      $rows[] = [$linkToPage, $linkToIssueList];
      // @todo add delete trashcan?
    }

    $render = [];
    $render[] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#prefix' => '<h2>' . t("Pages with the most issues") . '</h2>',
      '#cache' => [
        'contexts' => [
          'user',
          'url',
        ],
        'tags' => [
          'editoria11y.dashboard',
        ],
      ],
    ];
    $render[] = [
      'link' => [
        '#prefix' => '<p>',
        '#type' => 'link',
        '#title' => $this->t('All pages with issues'),
        '#attributes' => ['class' => 'button button--primary'],
        '#url' => Url::fromRoute('editoria11y.reports_pages'),
        '#suffix' => '<br>&nbsp;</p>',
      ],
    ];

    $render[] = [
      'spacer' => [
        '#type' => 'html_tag',
        '#tag' => 'p',
      ],
    ];

    return $render;
  }

  /**
   * Gets summary info on recent dismissals for dashboard panel.
   *
   * @return array
   *   Return the recent dismissals.
   */
  private function getRecentDismissals() {

    $result_service = \Drupal::service('editoria11y.dashboard');
    $lang = $this->dashboard->getPageLang();
    $results = $result_service->getRecentDismissals($lang);

    // ['page_title','route_name','page_path','created','updated','result_name',
    // 'dismissal_status','uid','created','updated', 'stale']);
    $header = [
      t('Page'),
      t('Issue'),
      t('Marked as'),
      t('User'),
      t('Date'),
    ];

    $rows = [];

    foreach ($results as $record) {
      $user = User::load($record->uid);
      $name = $user->getDisplayName();

      $url = UrlHelper::filterBadProtocol($record->page_path);
      $url .= strpos($url, '?') ? '&ed1ref=' . urlencode($url) : '?ed1ref=' . urlencode($url);
      $link = [
        'data' => [
          '#markup' => "<a href='" . $url . "'>" . $record->page_title . "</a>",
        ],
      ];
      $date = \Drupal::service('date.formatter')->format($record->created, 'short');

      // @todo return username instead of user id
      // @todo show stale as better than numerical boolean.
      $rows[] = [
        $link,
        $record->result_name,
        $record->dismissal_status,
        $name,
        $date,
      ];
    }

    $render = [];
    $render[] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => 'clear-both',
      ],
      'content' => [
        '#type' => 'table',
        '#prefix' => '<h2 id="recent_dismissals">' . t("Recent dismissals") . '</h2>',
        '#header' => $header,
        '#rows' => $rows,
        '#cache' => [
          'contexts' => [
            'user',
            'url',
          ],
          'tags' => [
            'editoria11y.dashboard',
          ],
        ],
      ],
    ];
    $render[] = [
      'link' => [
        '#prefix' => '<p>',
        '#type' => 'link',
        '#title' => $this->t('All dismissals'),
        '#attributes' => ['class' => 'button button--primary'],
        '#url' => Url::fromRoute('editoria11y.reports_dismissals'),
        '#suffix' => '<br>&nbsp;</p>',
      ],
    ];
    return $render;
  }

  /**
   * Builds table of top issues. Count limiter used for dashboard summary panel.
   *
   * @return array
   *   Returns all the top issues.
   */
  private function getTopIssues($count) {
    $result_service = \Drupal::service('editoria11y.dashboard');

    $prefix = "";
    $lang = "all";
    if ($count === 0 || $count > 25) {
      $language = \Drupal::request()->query->get('lang');
      if (!empty($language)) {
        $lang = Xss::filter($language);
      }
      else {
        $lang = 'all';
      }
      $links = $this->dashboard->getLanguageFilters($lang);
      // Add language filters to page.
      $prefix = $links;
    }
    $prefix .= "<h2>" . t("Most frequent issues") . "</h2>";

    $results = $result_service->getIssueList($count, $lang);

    $header = [
      t('Issue'),
      t('Count'),
    ];

    $rows = [];

    foreach ($results as $record) {
      $pages_by_issue_url = Url::fromUserInput("/admin/reports/editoria11y/issue?q=" . $record->result_name);
      $pages_by_issue = Link::fromTextAndUrl($record->result_name, $pages_by_issue_url)->toString();
      $rows[] = [$pages_by_issue, $record->result_name_count];
    }

    $render = [];
    $render[] = [
      '#type' => 'container',
      'content' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#prefix' => $prefix,
        '#cache' => [
          'contexts' => [
            'user',
            'url',
          ],
          'tags' => [
            'editoria11y.dashboard',
          ],
        ],
      ],
    ];
    if ($count > 0) {
      $render[] = [
        '#type' => 'container',
        '#prefix' => '<p>',
        '#suffix' => '<br>&nbsp;</p>',
        'content' => [
          'all' => [
            '#type' => 'link',
            '#title' => $this->t('Issues by type'),
            '#attributes' => ['class' => 'button button--primary'],
            '#url' => Url::fromRoute('editoria11y.reports_all_issues'),
            '#suffix' => ' ',
          ],
          'recent' => [
            '#type' => 'link',
            '#title' => $this->t('Issues by date'),
            '#attributes' => ['class' => 'button button--primary'],
            '#url' => Url::fromRoute('editoria11y.reports_recent'),
          ],
        ],
      ];
    }
    return $render;
  }

  /**
   * Builds page with all dismissals.
   *
   * @return array
   *   Get all the dismissals.
   */
  public function getDismissals() {

    $pager_manager = \Drupal::service('pager.manager');
    $page = $pager_manager
      ->findPage();

    // Load filters.
    $form_state = new FormState();
    $form_state->setRebuild();
    $form = \Drupal::formBuilder()->buildForm('Drupal\editoria11y\Form\DismissalFilters', $form_state);
    $form_title = $form_state->getValue('title');
    $form_url = $form_state->getValue('url');
    $form_filters = [
      'stale' => $form_state->getValue('stale'),
      'dismissal_status' => $form_state->getValue('status'),
      'uid' => $form_state->getValue('uid'),
      'result_name' => $form_state->getValue('result'),
      'page_language' => $form_state->getValue('lang'),
    ];
    $debugger = $form_state->getValues();
    $header = [
        ['data' => $this->t('Page with issue'), 'field' => 't.page_title'],
        ['data' => $this->t('Dismissed issue'), 'field' => 't.result_name'],
        ['data' => $this->t('Marked'), 'field' => 't.dismissal_status'],
    // Need join in query here. This won't sort right.
      t('By'),
        ['data' => $this->t('On'), 'field' => 't.created', 'sort' => 'desc'],
        ['data' => $this->t('Still on page'), 'field' => 't.stale'],
    ];

    $rows = [];

    $results = $this->dashboard->getDismissals($page, $header, $form_title, $form_url, $form_filters);
    $counter = 0;
    foreach ($results["results"] as $record) {
      $user = User::load($record->uid);
      $name = $user->getDisplayName();

      $url = UrlHelper::filterBadProtocol($record->page_path);
      $url .= strpos($url, '?') ? '&ed1ref=' . urlencode($url) : '?ed1ref=' . urlencode($url);
      $link = [
        'data' => [
          '#markup' => "<a href='" . $url . "'>" . $record->page_title . "</a>",
        ],
      ];

      // $userurl = Url::fromRoute("<current>", ["uid" => $record->uid]);
      // $userlink = Link::fromTextAndUrl($name, $userurl);
      $stale = $record->stale ? "No" : "Yes";

      $date = \Drupal::service('date.formatter')->format($record->created);

      // @todo return username instead of user id
      $rows[] = [
        $link,
        $record->result_name,
        $record->dismissal_status,
        [
          'data-uid' => $record->uid,
          'data' => $name,
        ], $date, $stale,
      ];

    }

    $pager_manager
      ->createPager($results["count"], 50);

    if (\Drupal::currentUser()->hasPermission('manage editoria11y results')) {
      $purger = '<span id="ed11y-rowpurger"></span>';
    }
    else {
      $purger = '';
    }

    $render = [];
    $render['form'] = [$form];
    $render[] = [
      '#type' => 'table',
      '#header' => $header,
      '#prefix' => $purger,
      '#rows' => $rows,
      '#cache' => [
        'contexts' => [
          'user',
          'url',
        ],
        'tags' => [
          'editoria11y.dashboard',
        ],
      ],
    ];
    $render[] = [
      '#type' => 'pager',
    ];
    return $render;
  }

  /**
   * Page: summary dashboard with three panels.
   *
   * @return array
   *   A simple renderable array.
   */
  public function getExportLinks(): array {
    $options = [
      'attributes' => [
        'download' => [
          'download',
        ],
      ],
    ];
    $issuesUrl = Url::fromRoute('editoria11y.exports_issues', [], $options);
    $issues = Link::fromTextAndUrl($this->t('Download issues report'), $issuesUrl)->toString();
    $dismissalsUrl = Url::fromRoute('editoria11y.exports_dismissals', [], $options);
    $dismissals = Link::fromTextAndUrl($this->t('Download dismissals report'), $dismissalsUrl)->toString();
    $summaryUrl = Url::fromRoute('editoria11y.exports_summary', [], $options);
    $summary = Link::fromTextAndUrl($this->t('Download summary report'), $summaryUrl)->toString();

    $render = [];
    $render[] = [
      '#markup' => '<h2>Export results</h2><ul><li>' . $summary . '</li><li>' . $issues . '</li><li>' . $dismissals . '</li></ul>',
    ];
    return $render;
  }

  /**
   * Page: summary dashboard with three panels.
   *
   * @return array
   *   A simple renderable array.
   */
  public function dashboard(): array {
    $this->syncStatus();

    $render = [
      '#type' => 'container',
      [
        '#type' => 'container',
        '#attributes' => [
          'class' => 'layout-row',
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => 'layout-column layout-column--half',
          ],
          'content' => [
            $this->getTopPages(),

          ],
        ],
        [
          '#type' => 'container',
          '#attributes' => [
            'class' => 'layout-column layout-column--half',
          ],
          'content' => [
            $this->getTopIssues(5),
          ],
        ],
      ],
    ];
    $render[] = [
      $this->getRecentDismissals(),
      $this->getExportLinks(),
    ];
    return $render;
  }

  /**
   * Page: pages with issues.
   *
   * @return array
   *   A simple renderable array.
   */
  public function results(): array {
    $this->syncStatus();
    return $this->getTestResults();
  }

  /**
   * Page: Recent alerts.
   *
   * @return array
   *   A simple renderable array.
   */
  public function recent(): array {
    $this->syncStatus();
    return $this->getRecentIssues();
  }

  /**
   * Page: list of issues by type.
   *
   * @return array
   *   A simple renderable array.
   */
  public function allIssues(): array {
    $this->syncStatus();
    return $this->getTopIssues(0);
  }

  /**
   * Page: pages with a specific issue.
   *
   * @return array
   *   A simple renderable array.
   */

  /**
   * Pages by issue function.
   *
   * @todo view by page or test, pagination
   */
  public function pagesByIssue(): array {
    $this->syncStatus();
    return $this->getPagesByIssue();
  }

  /**
   * Page: issues on a given page.
   *
   * @return array
   *   A simple renderable array.
   */

  /**
   * Function to see the issues by page.
   *
   * @todo view by page or test, pagination
   */
  public function issuesByPage(): array {
    $this->syncStatus();
    return $this->getIssuesByPage();
  }

  /**
   * Page: dismissals.
   *
   * @return array
   *   A simple renderable array.
   */

  /**
   * Dismissals function.
   *
   * @todo view by page or test, filter by user
   */
  public function dismissals(): array {
    $this->syncStatus();
    return $this->getDismissals();
  }

}
