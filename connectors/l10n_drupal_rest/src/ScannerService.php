<?php
declare(strict_types=1);

namespace Drupal\l10n_drupal_rest;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\file\FileRepositoryInterface;
use Drupal\l10n_server\ConnectorInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service description.
 */
class ScannerService {

  use StringTranslationTrait;

  const LAST_SYNC_TIME = 'l10n_drupal_rest_last_sync_time';

  const PROJECT_CONNECTOR_MODULE = 'drupal_rest:restapi';

  const PROJECT_PACKAGE_URL = 'https://www.drupal.org';

  const PROJECT_STATUS = 1;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $config;

  /**
   * A state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * An HTTP client service.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * A file repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * A file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  private FileSystemInterface $fileSystem;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private EntityTypeManagerInterface $entityTypeManager;

  /**
   * The connector instance.
   *
   * @var \Drupal\l10n_server\ConnectorInterface
   */
  private ConnectorInterface $connector;

  /**
   * A projects array.
   *
   * @var array
   */
  private array $projects;

  /**
   * The project count.
   *
   * @var int
   */
  private int $projectCount;

  /**
   * A releases array.
   *
   * @var array
   */
  private array $releases;

  /**
   * The release count.
   *
   * @var int
   */
  private int $releaseCount;

  /**
   * The filepath.
   *
   * @var string
   */
  private string $filepath;

  /**
   * Last sync time.
   *
   * @var int
   */
  private int $lastSyncTime;

  /**
   * Last sync before time.
   *
   * @var int
   */
  private int $lastSyncBeforeTime;

  /**
   * Constructs a DrupalRestService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state service.
   * @param \GuzzleHttp\ClientInterface $http_client
   *   The HTTP client.
   * @param \Drupal\file\FileRepositoryInterface $file_repository
   *   The file repository.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(
      ConfigFactoryInterface $config_factory,
      StateInterface $state,
      ClientInterface $http_client,
      FileRepositoryInterface $file_repository,
      FileSystemInterface $file_system,
      LoggerChannelFactoryInterface $logger_factory,
      EntityTypeManagerInterface $entity_type_manager
  ) {
    $this->config = $config_factory->get('l10n_server.settings');
    $this->state = $state;
    $this->httpClient = $http_client;
    $this->fileRepository = $file_repository;
    $this->fileSystem = $file_system;
    $this->logger = $logger_factory->get('l10n_drupal_rest');
    $this->entityTypeManager = $entity_type_manager;
    $this->projects = [];
    $this->projectCount = 0;
    $this->releases = [];
    $this->releaseCount = 0;
    $this->filepath = '';
    $this->lastSyncTime = 0;
    $this->lastSyncBeforeTime = 0;
  }

  /**
   * Sets connector.
   *
   * @param \Drupal\l10n_server\ConnectorInterface $connector
   *   The connector instance.
   *
   * @return $this
   */
  public function setConnector(ConnectorInterface $connector): self {
    $this->connector = $connector;
    return $this;
  }

  /**
   * The project count.
   *
   * @return int
   *   The project count integer.
   */
  public function getProjectCount(): int {
    return $this->projectCount;
  }

  /**
   * The release count.
   *
   * @return int
   *   The release count integer.
   */
  public function getReleaseCount(): int {
    return $this->releaseCount;
  }

  /**
   * Scans for new projects and/or releases.
   *
   * @return bool
   *   Boolean true on success, false on failure.
   */
  public function scan(): bool {
    try {
      $this->fetchProjectList();
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Error fetching project list: @code @message', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]));
      return FALSE;
    }

    try {
      $this->parseProjectList();
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Error parsing project list: @code @message', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]));
      return FALSE;
    }

    try {
      $this->storeProjectList();
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Error storing project list: @code @message', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]));
      return FALSE;
    }

    try {
      $this->storeReleaseList();
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Error storing release list: @code @message', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Fetch the project list.
   */
  private function fetchProjectList(): void {
    // Only sync releases which are at most one day older than our last sync
    // date. This ensures time zone issues and releases published while the
    // previous cron run will not be a problem, but we only look at a relatively
    // small list of releases at any given time. We only sync tagged releases,
    // which will not get rebuilt later anytime.
    $this->lastSyncTime = $this->state->get(static::LAST_SYNC_TIME, 0);
    $this->lastSyncBeforeTime = $this->lastSyncTime - 86400;

    // Fetch projects and releases since last sync.
    $this->filepath = 'temporary://releases.tsv';
    $url = $this->config->get('connectors.drupal_rest:restapi.source.restapi.refresh_url');

    // Add a timestamp GET parameter to prevent CDN caching.
    $url = Url::fromUri($url, ['query' => ['time' => time()]])->toString();

    // This will take some time, so we need to increase timeout.
    $response = $this->httpClient->get($url, ['connect_timeout' => 30]);

    // Save as temporary file.
    /** @var \Drupal\file\FileRepositoryInterface $fileRepository */
    $file_repository = \Drupal::service('file.repository');
    $file_repository->writeData(
      $response->getBody()->getContents(),
      $this->filepath,
      FileSystemInterface::EXISTS_RENAME
    );
  }

  /**
   * Parse the project list.
   */
  private function parseProjectList(): void {
    $headers = [];

    // Read from temporary file.
    if (($handle = fopen($this->filepath, "r")) !== FALSE) {
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
        if ($this->lastSyncBeforeTime < $time) {
          $machine_name = trim($data[$headers['project_machine_name']]);
          $title = trim($data[$headers['project_name']]);

          // A first array for projects.
          $this->projects[$machine_name] = $title;

          // A second array for releases.
          $this->releases[] = [
            'created' => $time,
            'machine_name' => $machine_name,
            'title' => $title,
            'version' => $data[$headers['version']],
          ];
        }
        else {
          fclose($handle);
        }
      }
      fclose($handle);
    }

    $this->fileSystem->delete($this->filepath);
  }

  /**
   * Store the project list.
   */
  private function storeProjectList(): void {
    $project_storage = $this->entityTypeManager
      ->getStorage('l10n_server_project');

    $this->projectCount = 0;
    foreach ($this->projects as $project_uri => $project_title) {
      $existing_projects = $project_storage->getQuery()
        ->condition('uri', $project_uri)
        ->execute();

      if ($existing_projects) {
        /** @var \Drupal\l10n_server\Entity\L10nServerProject $existing_project */
        $existing_project = $project_storage->load(reset($existing_projects));

        // Check that the title is correct, if not update it.
        if ($existing_project->label() !== $project_title) {
          $existing_project
            ->set('title', $project_title)
            ->save();

          $this->logger->info('Project %name renamed to %title.', [
            '%title' => $project_title,
            '%name' => $project_uri,
          ]);
        }
      }
      else {
        $this->projectCount++;
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

        $this->logger->notice('Project %title (%uri) added.', [
          '%title' => $project_title,
          '%uri' => $project_uri,
        ]);
      }
    }
  }

  /**
   * Store the release list.
   */
  private function storeReleaseList(): void {
    $project_storage = $this->entityTypeManager
      ->getStorage('l10n_server_project');
    $release_storage = $this->entityTypeManager
      ->getStorage('l10n_server_release');

    $this->releaseCount = 0;
    foreach ($this->releases as $release) {
      $download_link = "https://ftp.drupal.org/files/projects/{$release['machine_name']}-{$release['version']}.tar.gz";
      if ($release_storage->getQuery()->condition('download_link', $download_link)->execute()) {
        // @todo (D7) What happens to unpublished releases? drop data outright?
      }
      else {
        $this->releaseCount++;

        // Get the project id.
        $projects = $project_storage->getQuery()
          ->condition('uri', $release['machine_name'])
          ->range(0, 1)
          ->execute();
        $pid = reset($projects);

        // @todo (d7) What about filehash?
        $filehash = '';

        // New published release, not recorded before.
        $release_storage->create([
          'pid' => $pid,
          'title' => $this->t('@title @version', [
            '@title' => $release['title'],
            '@version' => $release['version'],
          ]),
          'version' => $release['version'],
          'download_link' => $download_link,
          'file_date' => $release['created'],
          'file_hash' => $filehash,
          'last_parsed' => 0,
          'weight' => 0,
        ])->save();

        $this->logger->notice('Release %title from project %name added.', [
          '%title' => $release['version'],
          '%name' => $release['machine_name'],
        ]);

        // Update last sync date with the date of this release if later.
        $this->lastSyncTime = max($this->lastSyncTime, $release['created']);
      }
    }
  }

}
