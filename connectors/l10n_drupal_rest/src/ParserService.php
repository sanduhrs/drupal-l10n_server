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
use Drupal\file\FileRepositoryInterface;
use Drupal\l10n_server\ConnectorInterface;
use Drupal\l10n_server\Entity\L10nServerError;
use Drupal\l10n_server\L10nHelper;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;
use GuzzleHttp\ClientInterface;

/**
 * Service description.
 */
class ParserService {

  use StringTranslationTrait;

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
   * The release.
   *
   * @var \Drupal\l10n_server\Entity\L10nServerReleaseInterface
   */
  private L10nServerReleaseInterface $release;

  /**
   * The files count.
   *
   * @var int
   *   The files count.
   */
  private int $filesCount;

  /**
   * The lines count.
   *
   * @var int
   *   The lines count.
   */
  private int $linesCount;

  /**
   * The strings count.
   *
   * @var int
   *   The strings count.
   */
  private int $stringsCount;

  /**
   * The errors parsing a release.
   *
   * @var int
   *   The errors count.
   */
  private int $errorsCount;

  /**
   * The filename.
   *
   * @var string
   */
  private string $filename;

  /**
   * The filename.
   *
   * @var string
   */
  private string $packageFile;

  /**
   * The temp path.
   *
   * @var string
   */
  private string $tempPath;

  /**
   * The files from a release.
   *
   * @var array
   */
  private array $files;

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
    $this->filesCount = 0;
    $this->linesCount = 0;
    $this->stringsCount = 0;
    $this->errorsCount = 0;
    $this->filename = '';
    $this->packageFile = '';
    $this->tempPath = '';
    $this->files = [];
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
   * Gets release.
   *
   * @return \Drupal\l10n_server\Entity\L10nServerReleaseInterface
   *   The release object.
   */
  public function getRelease(): L10nServerReleaseInterface {
    return $this->release;
  }

  /**
   * Sets release.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerReleaseInterface $release
   *   The release object.
   *
   * @return $this
   */
  public function setRelease(L10nServerReleaseInterface $release): self {
    $this->release = $release;
    return $this;
  }

  /**
   * The files count.
   *
   * @return int
   *   The files count integer.
   */
  public function getFilesCount(): int {
    return $this->filesCount;
  }

  /**
   * The lines count.
   *
   * @return int
   *   The lines count integer.
   */
  public function getLinesCount(): int {
    return $this->linesCount;
  }

  /**
   * The strings count.
   *
   * @return int
   *   The strings count integer.
   */
  public function getStringsCount(): int {
    return $this->stringsCount;
  }

  /**
   * The errors count.
   *
   * @return int
   *   The errors count integer.
   */
  public function getErrorsCount(): int {
    return $this->errorsCount;
  }

  /**
   * Parses a release.
   */
  public function parse(): bool {
    try {
      $this->downloadReleaseFile();
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Error downloading release file: @code @message', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]));
      return FALSE;
    }

    try {
      $this->unpackReleaseFile();
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Error downloading release file: @code @message', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]));
      return FALSE;
    }

    try {
      $this->processRelease();
    }
    catch (\Exception $e) {
      $this->logger->error($this->t('Error processing release: @code @message', [
        '@code' => $e->getCode(),
        '@message' => $e->getMessage(),
      ]));
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Downloads release file.
   *
   * @throws \Exception
   */
  private function downloadReleaseFile(): void {
    $this->filename = basename($this->release->getDownloadLink());
    $this->packageFile = $this->fileSystem->getTempDirectory() . '/' . $this->filename;

    $this->logger->notice('Retrieving @filename for parsing.', [
      '@filename' => $this->filename,
    ]);

    // Check filename for a limited set of allowed chars.
    if (!preg_match('!^([a-zA-Z0-9_.-])+$!', $this->filename)) {
      throw new \Exception('Filename ' . $this->filename . ' contains malicious characters.', 100);
    }

    // Already downloaded. Probably result of faulty file download left around,
    // so remove file.
    if (file_exists($this->packageFile)) {
      unlink($this->packageFile);
      $this->logger->warning('File @file already exists, deleting.', [
        '@file' => $this->packageFile,
      ]);
    }

    $response = $this->httpClient->get($this->release->getDownloadLink());
    file_put_contents($this->packageFile, $response->getBody()->getContents());
  }

  /**
   * Unpacks release file.
   */
  private function unpackReleaseFile(): void {
    // Generate temp folder and file to extract the tarball.
    $this->tempPath = $this->fileSystem->getTempDirectory() . '/drush_tmp_' . uniqid(time() . '_');
    if (!$this->fileSystem->mkdir($this->tempPath)) {
      throw new \Exception('Could not create temporary directory ' . $this->tempPath . '.', 110);
    }

    // Nothing to do if the file is not there.
    if (!file_exists($this->packageFile)) {
      throw new \Exception('Package to parse (' . $this->packageFile . ') does not exist.', 111);
    }

    // Extract the local file to the temporary directory.
    $command = escapeshellcmd("tar -xvvzf $this->packageFile -C $this->tempPath");
    $status = exec($command);
    if (!$status) {
      throw new \Exception('Failed to extract ' . $this->packageFile . '.', 112);
    }
  }

  /**
   * Processes a release.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function processRelease(): void {
    $release = clone $this->release;

    // Get all source files and save strings with our callback for this release.
    $release->uri = explode('-', $this->filename)[0];
    L10nHelper::releaseSetBranch($release);

    // This could take some time.
    @set_time_limit(0);
    \Drupal::moduleHandler()->loadInclude('potx', 'inc');
    \Drupal::moduleHandler()->loadInclude('potx', 'inc', 'potx.local');

    if ($release->core === 'all') {
      $version = POTX_API_8;
    }
    else {
      $version = explode('.', $release->core)[0];
    }

    potx_local_init($this->tempPath);
    potx_status('set', POTX_STATUS_STRUCTURED);

    $this->files = _potx_explore_dir($this->tempPath, '*', $version);
    $this->filesCount = count($this->files);

    L10nHelper::saveFile([$release->getProjectId(), $release->id()]);
    L10nHelper::addedStringCounter(NULL, TRUE);
    foreach ($this->files as $name) {
      _potx_process_file(
        $name,
        strlen($this->tempPath) + 1,
        'Drupal\l10n_server\L10nHelper::saveString',
        'Drupal\l10n_server\L10nHelper::saveFile',
        $version
      );
    }
    potx_finish_processing('Drupal\l10n_server\L10nHelper::saveFile', $version);

    $this->stringsCount = L10nHelper::addedStringCounter();
    $this->linesCount = (int) \Drupal::database()
      ->select('l10n_server_line', 'l')
      ->condition('l.rid', $this->release->id())
      ->countQuery()
      ->execute()
      ->fetchField();

    // Record changes of the scanned project in the database.
    $this->logger->notice('@filename (@files files, @sids strings) scanned.', [
      '@filename' => $this->filename,
      '@files' => $this->filesCount,
      '@sids' => $this->stringsCount,
    ]);

    // Get and store all messages recorded while parsing and clear the static
    // cache.
    $messages = potx_status('get', TRUE);
    $this->errorsCount = count($messages);
    foreach ($messages as $message) {
      L10nServerError::create([
        'rid' => $this->release->id(),
        'value' => t('@message At @excerpt in @file on line @line. Read more at @docs_url', [
          '@message' => is_object($message[0]) ? (string) $message[0] : '',
          '@file' => $message[1],
          '@line' => $message[2],
          '@excerpt' => $message[3],
          '@docs_url' => $message[4],
        ]),
      ])->save();
    }
  }

}
