<?php

declare(strict_types=1);

namespace Drupal\l10n_drupal_rest\Plugin\l10n_server\Connector;

use Drupal\Core\File\FileSystemInterface;
use Drupal\l10n_server\Annotation\Connector;
use Drupal\l10n_server\ConnectorPluginBase;
use Drush\Drush;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * A plugin to use source code of drupal.org package.
 *
 * @Connector(
 *   id = "drupal_rest",
 *   label = @Translation("Drupal.org packages"),
 *   deriver = "Drupal\l10n_server\Plugin\Derivative\ConnectorSources",
 *   supported_sources = {
 *    "restapi"
 *   }
 * )
 */
class DrupalRest extends ConnectorPluginBase {

  const LAST_SYNC =  'l10n_drupal_rest_last_sync';

  const REFRESH_URL = 'l10n_drupal_rest_refresh_url';

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $databaseConnection;

  /**
   * @var \Drupal\Core\State\StateInterface
   */
  private $state;

  /** @var \Drupal\Core\Entity\EntityTypeManagerInterface */
  private $entityTypeManager;

  /**
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private $logger;

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    $instance = parent::create(
      $container,
      $configuration,
      $plugin_id,
      $plugin_definition
    );
    $instance->fileSystem = $container->get('file_system');
    $instance->httpClient = $container->get('http_client');
    $instance->databaseConnection = $container->get('database');
    $instance->state = $container->get('state');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->logger = $container->get('logger.factory')->get('l10n_drupal_rest');
    return $instance;
  }

  // @todo: type $release (query result object).

  /**
   * @throws \Exception
   */
  public function drupalOrgParseRelease($release): bool {
    $filename = basename($release->download_link);
    $package_file = $this->fileSystem->getTempDirectory() . '/' . $filename;

    $this->logger->notice(
      'Retrieving @filename for parsing.',
      ['@filename' => $filename]
    );

    // Check filename for a limited set of allowed chars.
    if (!preg_match('!^([a-zA-Z0-9_.-])+$!', $filename)) {
      $this->logger->error(
        'Filename %file contains malicious characters.',
        ['%file' => $package_file]
      );
      return FALSE;
    }

    // Already downloaded. Probably result of faulty file download left around,
    // so remove file.
    if (file_exists($package_file)) {
      unlink($package_file);
      $this->logger->warning(
        'File %file already exists, deleting.',
        ['%file' => $package_file]
      );
    }

    // Download the tar.gz file from Drupal.org and save it.
    if (!(($contents = $this->httpClient->get($release->download_link))
      && ($contents->code === 200)
      && file_put_contents($package_file, $contents->data))) {

      $this->logger->error(
        'Unable to download and save %download_link file (%error).',
        [
          '%download_link' => $release->download_link,
          '%error' => $contents->code . ' ' . $contents->error,
        ]
      );
      return FALSE;
    }

    // Set up status messages if not in automated mode.
    //@todo: Check this call is still operational.
    potx_status('set', POTX_STATUS_MESSAGE);

    // Generate temp folder to extract the tarball.
    $temp_path = drush_tempdir();

    // Nothing to do if the file is not there.
    if (!file_exists($package_file)) {
      $this->logger->error(
        'Package to parse (%file) does not exist.',
        ['%file' => $package_file]
      );
      return FALSE;
    }

    // Extract the local file to the temporary directory.
    if (!Drush::process(['tar', '-xvvzf', $package_file, '-C', $temp_path])) {
      $this->logger->error(
        'Failed to extract %file.',
        ['%file' => $package_file]
      );
      return FALSE;
    }

    $this->logger->notice(
      'Parsing extracted @filename for strings.',
      ['@filename' => $filename]
    );

    // Get all source files and save strings with our callback for this release.
    $release->uri = explode('-', $filename)[0];
    //@todo: Check this call is still operational.
    l10n_packager_release_set_branch($release);
    if ($release->core === 'all') {
      $version = POTX_API_8;
    }
    else {
      $version = explode('.', $release->core)[0];
    }
    _l10n_drupal_potx_init();
    $files = _potx_explore_dir($temp_path, '*', $version);
    l10n_drupal_save_file([$release->pid, $release->rid]);
    l10n_drupal_added_string_counter(NULL, TRUE);
    foreach ($files as $name) {
      _potx_process_file(
        $name,
        strlen($temp_path) + 1,
        'l10n_drupal_save_string',
        'l10n_drupal_save_file',
        $version
      );
    }
    potx_finish_processing('l10n_drupal_save_string', $version);

    $sid_count = l10n_drupal_added_string_counter();

    // Delete directory now that parsing is done.
    Drush::process(['rm', '-rf', $temp_path]);
    unlink($package_file);

    // Record changes of the scanned project in the database.
    $this->logger->notice(
      '@filename (@files files, @sids strings) scanned.',
      [
        '@filename' => $filename,
        '@files' => count($files),
        '@sids' => $sid_count,
      ]
    );

    // Parsed this releases files.
    $this->databaseConnection->update('l10n_server_release')
      ->fields([
        'sid_count' => $sid_count,
        'last_parsed' => \Drupal::time()->getRequestTime(),
      ])
      ->condition('rid', $release->rid)
      ->execute();

    // Update error list for this release. Although the errors are related to
    // files, we are not interested in the fine details, the file names are in
    // the error messages as text. We assume no other messages are added while
    // importing, so we can safely use drupal_get_message() to grab our errors.
    $this->databaseConnection->delete('l10n_server_error')->condition(
      'rid',
      $release->rid
    )->execute();
    $messages = $this->messenger->messagesByType('error');
    if (isset($messages['error']) && is_array($messages['error'])) {
      foreach ($messages['error'] as $error_message) {
        $this->databaseConnection
          ->insert('l10n_server_error')
          ->fields([
            'rid' => $release->rid,
            'value' => $error_message,
          ])
          // @todo: catch Exception?
          ->execute();
      }
    }

    // @todo: Implement a better caching strategy (tags).
    // Clear stats cache, so new data shows up.
    // cache_clear_all('l10n:stats', 'cache');

    return TRUE;
  }

  /**
   * Synchronizes the project list.
   *
   * // @todo source type
   * @param $source
   *
   * @return void
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function refreshProjectList($source) {
    $connector_name = 'l10n_drupal_rest_restapi';
    $projects = $releases = [];
    $project_count = $release_count = 0;

    // Only sync releases which are at most one day older then our last
    // sync date. This ensures time zone issues and releases published while the
    // previous cron run will not be a problem, but we only look at a relatively
    // small list of releases at any given time. We only sync tagged releases,
    // which will not get rebuilt later anytime.
    $last_sync = $this->state->get(static::LAST_SYNC, 0);
    $before = $last_sync - 86400;

    // Fetch projects and releases since last sync.
    $file_path = 'temporary://releases.tsv';
    // @todo get from configuration
    $url = $this->state->get(static::REFRESH_URL, L10N_DRUPAL_REST_REFRESH_URL);
    // Add a timestamp GET parameter to prevent CDN caching.
    $url = Url::fromUri($url, ['query' => ['time' => time()]])->toString();

    // This will take some time, so we need to increase timeout.
    // @todo check d7 options mapping.
    //$response = drupal_http_request($url, array(), 'GET', NULL, 3, 300);
    $response = $this->httpClient->get($url, ['timeout' => 300]);

    if ($response->getStatusCode() === Response::HTTP_OK) {
      // Save as temporary file and release the memory.
      try {
        /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
        $file_repository = \Drupal::service('file.repository');
        $file_repository->writeData((string) $response->getBody(), $file_path, FileSystemInterface::EXISTS_RENAME);
        unset($response);
        $this->readTsv($file_path, $before, $projects, $releases);
        // Remove file
        $this->fileSystem->delete($file_path);
      }
      catch (\Exception $exception) {
        $this->logger->error($exception->getMessage());
      }
    }
    else {
      $this->logger->error('Releases URL %url is unreacheable.', [
        '%url' => $url,
      ]);
      return;
    }

    // Record all non-existing projects in our local database.
    $project_storage = $this->entityTypeManager->getStorage('l10n_server_project');
    foreach ($projects as $project_name => $project_title) {
      // Check if project exists.
      if ($existing_projects = $project_storage->getQuery()->condition('uri', $project_name)->execute()) {
        /** @var \Drupal\l10n_server\Entity\Project $existing_project */
        $existing_project = $project_storage->load(reset($existing_projects));
        // Check that the title is correct, if not update it.
        if ($existing_project->get('title')->value !== $project_title) {
          $existing_project->set('title', $project_title)->save();
          $this->logger->info('Project %n renamed to %t.', [
            '%t' => $project_title,
            '%n' => $project_name,
          ]);
        }
      }
      else {
        $project_count++;
        $project_storage->create(
          [
            'uri' => $project_name,
            'title' => $project_title,
            'last_parsed' =>  $_SERVER['REQUEST_TIME'],
            // @todo home_link (D7) was changed to homepage (D9) check usage.
            'homepage' => 'http://drupal.org/project/' . $project_name,
            'connector_module' => $connector_name,
            'status' => 1,
          ]
        )->save();
        $this->logger->notice('Project %t (%n) added.', array(
          '%t' => $project_title,
          '%n' => $project_name,
        ));
      }
    }

    // Record all releases in our local database.
    $release_storage = $this->entityTypeManager->getStorage('l10n_server_release');
    foreach ($releases as $release) {
      $download_link = "http://ftp.drupal.org/files/projects/{$release['machine_name']}-{$release['version']}.tar.gz";
      if ($release_storage->getQuery()->condition('download_link', $download_link)->execute()) {
        // @todo (D7) What happens to unpublished releases? drop data outright?
      }
      else {
        $release_count++;
        // Get the project pid
        $projects = $project_storage->getQuery()->condition('uri', $release['machine_name'])->execute();
        $pid = reset($projects);
        // @todo (d7) What about filehash?
        $filehash = '';
        // New published release, not recorded before.
        $release_storage->create([
          'pid'           => $pid,
          'title'         => $release['version'],
          'download_link' => $download_link,
          'file_date'     => $release['created'],
          'file_hash'     => $filehash,
          'last_parsed'   => 0,
          'weight'        => 0,
        ])->save();
        $this->logger->notice('Release %t from project %n added.', [
          '%t' => $release['version'],
          '%n' => $release['machine_name'],
        ]);
        // Update last sync date with the date of this release if later.
        $last_sync = max($last_sync, $release['created']);
      }
    }

    // Report some informations.
    if ($release_count || $project_count) {
      $this->logger->notice('Fetched info about %p projects and %r releases.', [
        '%p' => $project_count,
        '%r' => $release_count,
      ]);
    }
    else {
      $this->logger->notice('No new info about projects and releases.');
    }

    // Set last sync time to limit number of releases to look at next time.
    $this->state->set(static::LAST_SYNC, $last_sync);
  }

  /**
   * Parse the release file for projects and releases newer than before
   *
   * @param $file_path
   * @param $before
   * @param $projects
   * @param $releases
   * @return mixed
   */
   private function readTsv($file_path, $before, &$projects, &$releases) {
    $headers = array();
    if (($handle = fopen($file_path, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
        // Get headers
        if (empty($headers)) {
          $headers = array_flip($data);
          continue;
        }
        // Filter out sandboxes and malformed releases.
        if (count($data) < 4 || is_numeric($data[$headers['project_machine_name']])) {
          continue;
        }
        $time = strtotime($data[$headers['created']]);
        if ($before < $time) {
          $machine_name = trim($data[$headers['project_machine_name']]);
          $title = trim($data[$headers['project_name']]);
          // A first array for projects.
          $projects[$machine_name] = $title;
          // A second array for releases.
          $releases[] = array(
            'created' => $time,
            'machine_name' => $machine_name,
            'title'        => $title,
            'version'      => $data[$headers['version']],
          );
        }
        else {
          fclose($handle);
          return TRUE;
        }
      }
    }
    return FALSE;
  }

}
