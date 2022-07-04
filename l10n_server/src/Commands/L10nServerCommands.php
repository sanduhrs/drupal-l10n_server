<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Commands;

use Drupal\Component\Datetime\Time;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\l10n_server\ConnectorManagerInterface;
use Drupal\l10n_server\Entity\L10nServerProject;
use Drupal\l10n_server\Entity\L10nServerRelease;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
class L10nServerCommands extends DrushCommands {

  /**
   * Configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected ImmutableConfig $configuration;

  /**
   * Connector manager.
   *
   * @var \Drupal\l10n_server\ConnectorManagerInterface
   */
  protected ConnectorManagerInterface $connectorManager;

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Queue factory.
   *
   * @var \Drupal\Core\Queue\QueueFactory
   */
  protected QueueFactory $queueFactory;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Time service.
   *
   * @var \Drupal\Component\Datetime\Time
   */
  private Time $time;

  /**
   * Class constructor.
   */
  public function __construct(
      ConfigFactory $config_factory,
      Connection $database,
      QueueFactory $queue_factory,
      EntityTypeManagerInterface $entity_type_manager,
      Time $time,
      ConnectorManagerInterface $connector_manager
  ) {
    parent::__construct();
    $this->configuration = $config_factory->get('l10n_server.settings');
    $this->database = $database;
    $this->queueFactory = $queue_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->time = $time;
    $this->connectorManager = $connector_manager;
  }

  /**
   * Scans for new projects and/or releases.
   *
   * @param string $connector
   *   The connector to scan.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option all
   *   Scans all enabled connectors.
   *
   * @usage l10n_server-scan drupal_rest:restapi
   *   Scans the 'drupal_rest:restapi' connector.
   * @usage l10n_server-scan --all
   *   Scans all enabled connectors.
   *
   * @command l10n_server:scan
   *
   * @aliases lss
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  // phpcs:ignore
  public function scan(string $connector = 'drupal_rest:restapi', array $options = ['all' => FALSE]): void {
    $connectors = [$connector];

    if ($options['all']) {
      $connectors = $this->configuration->get('enabled_connectors');
    }

    $results = [];
    $project_count = $release_count = $connector_count = 0;
    foreach ($connectors as $connector_id) {
      /** @var \Drupal\l10n_server\ConnectorInterface $connector */
      $connector = $this->connectorManager->createInstance($connector_id);
      $source = $connector->getSourceInstance();

      if (!$connector->isEnabled()) {
        $this->logger()->notice(dt('The connector @connector_label @source_label is not enabled.', [
          '@connector_label' => $connector->getLabel(),
          '@source_label' => $source->getLabel(),
        ]));
        continue;
      }

      if ($connector->isScannable()) {
        $connector_count++;
        $this->logger()->notice(dt('Scanning @connector_label @source_label...', [
          '@connector_label' => $connector->getLabel(),
          '@source_label' => $source->getLabel(),
        ]));

        /** @var \Drupal\l10n_server\ConnectorScanHandlerResultInterface $result */
        $result = $connector->scanHandler();
        $results[$connector->getPluginId()][] = $result;
        $project_count = $project_count + $result->getProjectCount();
        $release_count = $release_count + $result->getReleaseCount();
      }
      else {
        $this->logger()->error(dt('The connector @connector_label with source @source_label is not scannable.', [
          '@connector_label' => $connector->getLabel(),
          '@source_label' => $source->getLabel(),
        ]));
      }
    }

    if ($results) {
      $this->logger()->success(dt('Scanned @projects project(s) and @releases release(s) in @connectors connector(s).', [
        '@connectors' => $connector_count,
        '@projects' => $project_count,
        '@releases' => $release_count,
      ]));
    }
    else {
      $this->logger()->info(dt('No new project(s) or release(s) scanned.'));
    }
  }

  /**
   * Queue releases for parsing.
   *
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option force
   *
   * @usage l10n_server-queue
   *   Queue releases for parsing.
   *
   * @command l10n_server:queue
   *
   * @aliases lsq
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function queue(array $options = ['force' => NULL]): void {

    if ($options['force']) {
      $this->database
        ->update('l10n_server_release')
        ->fields(['queued' => 0])
        ->execute();
    }

    // Queue releases to be parsed.
    $queue = $this->queueFactory->get('l10n_server_parser_queue');
    $storage = $this
      ->entityTypeManager
      ->getStorage('l10n_server_release');
    $ids = $storage->getIdsToQueue();
    $this->logger()->notice(dt('Queuing releases to be parsed...'));

    $i = 0;
    $releases = $storage->loadMultiple($ids);
    foreach ($releases as $release) {
      if ($queue->createItem($release)) {
        // Add timestamp to avoid queueing item more than once.
        $release->setQueuedTime($this->time->getRequestTime());
        $release->save();
        $i++;
      }
    }

    $this->logger()->notice(dt('Found @count releases, @queued queued for parsing.', [
      '@count' => count($releases),
      '@queued' => $i,
    ]));
  }

  /**
   * Parses releases.
   *
   * @param string $project
   *   The project to scan.
   * @param array $options
   *   An associative array of options whose values come from cli, aliases,
   *   config, etc.
   *
   * @option all
   *
   * @usage l10n_server-parse 'Drupal core'
   *   Scan releases of the 'Drupal core' project.
   *
   * @command l10n_server:parse
   *
   * @aliases lsp
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  // phpcs:ignore
  public function parse(string $project = '', array $options = ['release' => NULL, 'limit' => 1, 'only-unparsed' => FALSE, 'only-unqueued' => FALSE]): void {
    $query = $this->database
      ->select('l10n_server_release', 'r');
    $query
      ->join('l10n_server_project', 'p', 'r.pid = p.pid');
    $query
      ->fields('r')
      ->orderBy('file_date', 'ASC');

    if ($project) {
      $query->condition('p.title', $project);
    }

    if ($release = $options['release']) {
      $query->condition('r.version', $release);
    }

    if ($options['only-unparsed']) {
      $query->condition('r.last_parsed', 0);
    }

    if ($options['only-unqueued']) {
      $query->condition('r.queued', 0);
    }

    if ($limit = $options['limit']) {
      $query->range(0, $limit);
    }
    $rows = $query->execute()->fetchAllAssoc('rid');

    $results = [];
    $files_count = $strings_count = $errors_count = $connector_count = 0;
    foreach ($rows as $row) {
      $project = L10nServerProject::load($row->pid);
      $release = L10nServerRelease::load($row->rid);

      /** @var \Drupal\l10n_server\ConnectorInterface $connector */
      $connector = $this->connectorManager->createInstance($project->getConnectorModule());
      $source = $connector->getSourceInstance();

      if (!$connector->isEnabled()) {
        $this->logger()->notice(dt('The connector @connector_label @source_label is not enabled.', [
          '@connector_label' => $connector->getLabel(),
          '@source_label' => $source->getLabel(),
        ]));
        continue;
      }

      if ($connector->isParsable()) {
        $connector_count++;
        $this->logger()->notice(dt('Parsing @connector_label @source_label...', [
          '@connector_label' => $connector->getLabel(),
          '@source_label' => $source->getLabel(),
        ]));

        /** @var \Drupal\l10n_server\ConnectorParseHandlerInterface $connector */
        $connector->setRelease($release);
        /** @var \Drupal\l10n_server\ConnectorParseHandlerResultInterface $result */
        if ($result = $connector->parseHandler()) {
          $release = $connector->getRelease();
          $release
            ->setSourceStringCount($result->getStringCount())
            ->setLineCount($result->getLineCount())
            ->setFileCount($result->getFileCount())
            ->setErrorCount($result->getErrorCount())
            ->setLastParsed(time())
            ->setQueuedTime(0)
            ->save();
          $results[$connector->getPluginId()][] = $result;
          $files_count = $files_count + $result->getFileCount();
          $strings_count = $strings_count + $result->getStringCount();
          $errors_count = $errors_count + $result->getErrorCount();
        }
      }
      else {
        $this->logger()->error(dt('The connector @connector_label with source @source_label is not scannable.', [
          '@connector_label' => $connector->getLabel(),
          '@source_label' => $source->getLabel(),
        ]));
      }
    }

    if ($results) {
      $this->logger()->success(dt('Scanned @files file(s) and @strings string(s) in @connectors connector(s) with @errors error(s).', [
        '@connectors' => $connector_count,
        '@files' => $files_count,
        '@strings' => $strings_count,
        '@errors' => $errors_count,
      ]));
    }
    else {
      $this->logger()->info(dt('No new project(s) or release(s) scanned.'));
    }
  }

}
