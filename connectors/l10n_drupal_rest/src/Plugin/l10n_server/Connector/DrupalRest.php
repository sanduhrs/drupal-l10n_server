<?php

declare(strict_types=1);

namespace Drupal\l10n_drupal_rest\Plugin\l10n_server\Connector;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystem;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\l10n_server\ConnectorPluginBase;
use Drupal\Core\Url;
use Drupal\l10n_server\Entity\Release;
use Drupal\l10n_server\SourceInterface;
use Drush\Drush;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

  const LAST_SYNC = 'l10n_drupal_rest_last_sync';

  const PROJECT_CONNECTOR_MODULE = 'drupal_rest:restapi';

  const PROJECT_PACKAGE_URL = 'https://www.drupal.org';

  const PROJECT_STATUS = 1;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystem
   */
  private FileSystem $fileSystem;

  /**
   * HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  private Client $httpClient;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  private Connection $database;

  /**
   * State system.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  private StateInterface $state;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  private LoggerChannelInterface $logger;

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
    $instance->database = $container->get('database');
    $instance->state = $container->get('state');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->logger = $container->get('logger.factory')
      ->get('l10n_drupal_rest');
    return $instance;
  }

  /**
   * Parse a drupal.org release.
   *
   * @param \Drupal\l10n_server\Entity\Release $release
   *   The release object.
   *
   * @return bool
   *   Returns true on success, false on failure.
   *
   * @throws \Exception
   */
  public function drupalOrgParseRelease(Release $release): bool {
    $filename = basename($release->getDownloadLink());
    $package_file = $this->fileSystem->getTempDirectory() . '/' . $filename;

    $this->logger
      ->notice('Retrieving @filename for parsing.', [
        '@filename' => $filename,
      ]);

    // Check filename for a limited set of allowed chars.
    if (!preg_match('!^([a-zA-Z0-9_.-])+$!', $filename)) {
      $this->logger
        ->error('Filename %file contains malicious characters.', [
          '%file' => $package_file,
        ]);
      return FALSE;
    }

    // Already downloaded. Probably result of faulty file download left around,
    // so remove file.
    if (file_exists($package_file)) {
      unlink($package_file);
      $this->logger
        ->warning('File %file already exists, deleting.', [
          '%file' => $package_file,
        ]);
    }

    try {
      $response = $this->httpClient->get($release->getDownloadLink());
      file_put_contents($package_file, $response->getBody());
    }
    catch (\Exception $e) {
      $this->logger
        ->error('Unable to download and save @download_link file (@error @message).', [
          '@download_link' => $release->getDownloadLink(),
          '@error' => $e->getCode(),
          '@message' => $e->getMessage(),
        ]);
      return FALSE;
    }

    // Potx module is already a dependency.
    module_load_include('inc', 'potx', 'potx');
    module_load_include('inc', 'potx', 'potx.local');

    // Set up status messages if not in automated mode.
    potx_status('set', POTX_STATUS_SILENT);

    // Generate temp folder to extract the tarball.
    $temp_path = drush_tempdir();

    // Nothing to do if the file is not there.
    if (!file_exists($package_file)) {
      $this->logger
        ->error('Package to parse (%file) does not exist.', [
          '%file' => $package_file,
        ]);
      return FALSE;
    }

    // Extract the local file to the temporary directory.
    $status_code = Drush::shell("tar -xvvzf $package_file -C $temp_path")->run();
    if ($status_code) {
      $this->logger
        ->error('Failed to extract %file.', [
          '%file' => $package_file,
        ]);
      return FALSE;
    }

    $this->logger
      ->notice('Parsing extracted @filename for strings.', [
        '@filename' => $filename,
      ]);

    module_load_include('inc', 'l10n_packager', 'l10n_packager');
    module_load_include('inc', 'l10n_drupal', 'l10n_drupal.potx');
    module_load_include('inc', 'l10n_drupal', 'l10n_drupal.files');

    // Get all source files and save strings with our callback for this release.
    $release->uri = explode('-', $filename)[0];
    l10n_packager_release_set_branch($release);
    if ($release->core === 'all') {
      $version = POTX_API_8;
    }
    else {
      $version = explode('.', $release->core)[0];
    }
    _l10n_drupal_potx_init();
    potx_local_init($temp_path);
    $files = _potx_explore_dir($temp_path, '*', $version);
    l10n_drupal_save_file([$release->getProjectId(), $release->id()]);
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
    //Drush::shell("rm -rf $temp_path")->run();
    unlink($package_file);

    // Record changes of the scanned project in the database.
    $this->logger
      ->notice(
        '@filename (@files files, @sids strings) scanned.',
        [
          '@filename' => $filename,
          '@files' => count($files),
          '@sids' => $sid_count,
        ]
      );

    // Parsed this releases files.
    $this->database
      ->update('l10n_server_release')
      ->fields([
        'sid_count' => $sid_count,
        'last_parsed' => \Drupal::time()->getRequestTime(),
      ])
      ->condition('rid', $release->id())
      ->execute();

    // Update error list for this release. Although the errors are related to
    // files, we are not interested in the fine details, the file names are in
    // the error messages as text. We assume no other messages are added while
    // importing, so we can safely use drupal_get_message() to grab our errors.
    $this->database
      ->delete('l10n_server_error')
      ->condition('rid', $release->id())
      ->execute();
    $messages = \Drupal::messenger()
      ->messagesByType('error');
    if (isset($messages['error'])
        && is_array($messages['error'])) {
      foreach ($messages['error'] as $error_message) {
        $this->database
          ->insert('l10n_server_error')
          ->fields([
            'rid' => $release->id(),
            'value' => $error_message,
          ])
          ->execute();
      }
    }
    return TRUE;
  }

  /**
   * Refresh project list.
   *
   * @param \Drupal\l10n_server\SourceInterface $source
   *   The source plugin.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function refreshProjectList(SourceInterface $source) {
    $config = $this->configFactory->get('l10n_server.settings');
    $projects = $releases = [];
    $project_count = $release_count = 0;

    // Only sync releases which are at most one day older than our last
    // sync date. This ensures time zone issues and releases published while the
    // previous cron run will not be a problem, but we only look at a relatively
    // small list of releases at any given time. We only sync tagged releases,
    // which will not get rebuilt later anytime.
    $last_sync = $this->state->get(static::LAST_SYNC, 0);
    $before = $last_sync - 86400;

    // Fetch projects and releases since last sync.
    $file_path = 'temporary://releases.tsv';
    $url = $config->get('connectors.drupal_rest:restapi.source.restapi.refresh_url');
    // Add a timestamp GET parameter to prevent CDN caching.
    $url = Url::fromUri($url, ['query' => ['time' => time()]])->toString();

    try {
      // This will take some time, so we need to increase timeout.
      $response = $this->httpClient->get($url, ['connect_timeout' => 30]);

      // Save as temporary file and release the memory.
      /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
      $file_repository = \Drupal::service('file.repository');
      $file_repository->writeData((string) $response->getBody(), $file_path, FileSystemInterface::EXISTS_RENAME);
      unset($response);
      $this->readTsv($file_path, $before, $projects, $releases);
      $this->fileSystem->delete($file_path);
    }
    catch (\Exception $exception) {
      $this->logger
        ->error($exception->getMessage());
    }

    // Record all non-existing projects in our local database.
    $project_storage = $this->entityTypeManager->getStorage('l10n_server_project');
    foreach ($projects as $project_uri => $project_title) {
      $existing_projects = $project_storage->getQuery()
        ->condition('uri', $project_uri)
        ->execute();
      if ($existing_projects) {
        /** @var \Drupal\l10n_server\Entity\Project $existing_project */
        $existing_project = $project_storage->load(reset($existing_projects));

        // Check that the title is correct, if not update it.
        if ($existing_project->get('title')->value !== $project_title) {
          $existing_project->set('title', $project_title)->save();
          $this->logger
            ->info('Project %name renamed to %title.', [
              '%title' => $project_title,
              '%name' => $project_uri,
            ]);
        }
      }
      else {
        $project_count++;
        $project_storage->create([
          'uri' => $project_uri,
          'title' => $project_title,
          'last_parsed' => \Drupal::time()->getRequestTime(),
          'homepage' => implode('/', [
            static::PROJECT_PACKAGE_URL,
            'project',
            $project_uri,
          ]),
          'connector_module' => self::PROJECT_CONNECTOR_MODULE,
          'status' => self::PROJECT_STATUS,
        ])->save();
        $this->logger
          ->notice('Project %title (%uri) added.', [
            '%title' => $project_title,
            '%uri' => $project_uri,
          ]);
      }
    }

    // Record all releases in our local database.
    $release_storage = $this->entityTypeManager->getStorage('l10n_server_release');
    foreach ($releases as $release) {
      $download_link = "https://ftp.drupal.org/files/projects/{$release['machine_name']}-{$release['version']}.tar.gz";
      if ($release_storage->getQuery()->condition('download_link', $download_link)->execute()) {
        // @todo (D7) What happens to unpublished releases? drop data outright?
      }
      else {
        $release_count++;
        // Get the project id.
        $projects = $project_storage->getQuery()
          ->condition('uri', $release['machine_name'])
          ->execute();
        $pid = reset($projects);
        // @todo (d7) What about filehash?
        $filehash = '';
        // New published release, not recorded before.
        $release_storage->create([
          'pid' => $pid,
          'title' => $release['version'],
          'download_link' => $download_link,
          'file_date' => $release['created'],
          'file_hash' => $filehash,
          'last_parsed' => 0,
          'weight' => 0,
        ])->save();
        $this->logger
          ->notice('Release %title from project %name added.', [
            '%title' => $release['version'],
            '%name' => $release['machine_name'],
          ]);
        // Update last sync date with the date of this release if later.
        $last_sync = max($last_sync, $release['created']);
      }
    }

    // Report some information.
    if ($release_count || $project_count) {
      $this->logger
        ->notice('Fetched info about %p projects and %r releases.', [
          '%p' => $project_count,
          '%r' => $release_count,
        ]);
    }
    else {
      $this->logger
        ->notice('No new info about projects and releases.');
    }

    // Set last sync time to limit number of releases to look at next time.
    $this->state->set(static::LAST_SYNC, $last_sync);
  }

  /**
   * Parse the release file for projects and releases newer than before.
   *
   * @param string $file_path
   *   The file path string.
   * @param int $before
   *   The timestamp before.
   * @param array $projects
   *   The projects array.
   * @param array $releases
   *   The releases array.
   *
   * @return bool
   *   Return true for success or false for failure.
   */
  private function readTsv(string $file_path, int $before, array &$projects, array &$releases): bool {
    $headers = [];
    if (($handle = fopen($file_path, "r")) !== FALSE) {
      while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
        // Get headers.
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
          $releases[] = [
            'created' => $time,
            'machine_name' => $machine_name,
            'title'        => $title,
            'version'      => $data[$headers['version']],
          ];
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
