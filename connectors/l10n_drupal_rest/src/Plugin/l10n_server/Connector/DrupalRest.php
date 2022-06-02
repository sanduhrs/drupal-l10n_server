<?php
declare(strict_types=1);

namespace Drupal\l10n_drupal_rest\Plugin\l10n_server\Connector;

use Drupal\Core\File\FileSystemInterface;
use Drupal\l10n_drupal_rest\ParserService;
use Drupal\l10n_drupal_rest\ScannerService;
use Drupal\l10n_server\ConnectorParseHandlerResultInterface;
use Drupal\l10n_server\ConnectorParseHandlerResult;
use Drupal\l10n_server\ConnectorPluginBase;
use Drupal\l10n_server\ConnectorScanHandlerResult;
use Drupal\l10n_server\ConnectorScanHandlerResultInterface;
use Drupal\l10n_server\ConnectorParseHandlerInterface;
use Drupal\l10n_server\ConnectorScanHandlerInterface;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A plugin to use source code of drupal.org package.
 *
 * @Connector(
 *   id = "drupal_rest",
 *   label = @Translation("Drupal.org packages"),
 *   deriver = "Drupal\l10n_server\Plugin\Derivative\ConnectorSources",
 *   supported_sources = {
 *     "restapi",
 *   }
 * )
 */
class DrupalRest extends ConnectorPluginBase implements ConnectorScanHandlerInterface, ConnectorParseHandlerInterface {

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * HTTP Client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected ClientInterface $httpClient;

  /**
   * Scanner service.
   *
   * @var \Drupal\l10n_drupal_rest\ScannerService
   */
  protected ScannerService $scanner;

  /**
   * Parser service.
   *
   * @var \Drupal\l10n_drupal_rest\ParserService
   */
  protected ParserService $parser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fileSystem = $container->get('file_system');
    $instance->httpClient = $container->get('http_client');
    $instance->scanner = $container->get('l10n_drupal_rest.scanner');
    $instance->parser = $container->get('l10n_drupal_rest.parser');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function parseHandler(L10nServerReleaseInterface $release = NULL): ConnectorParseHandlerResultInterface {
    if (!$release) {
      /** @var \Drupal\l10n_server\Entity\L10nServerReleaseStorage $release_storage */
      $release_storage = \Drupal::entityTypeManager()->getStorage('l10n_server_release');
      $release_ids = $release_storage->getIdsToRefresh();
      if ($release_ids) {
        $release = $release_storage->load(reset($release_ids));
      }
      else {
        return new ConnectorParseHandlerResult();
      }
    }

    $this->parser->setConnector($this);
    $this->parser->setRelease($release);
    if ($this->parser->parse()) {
      return new ConnectorParseHandlerResult([
        'files' => $this->parser->getFilesCount(),
        'lines' => $this->parser->getLinesCount(),
        'strings' => $this->parser->getStringsCount(),
      ]);
    }
    return new ConnectorParseHandlerResult();
  }

  /**
   * {@inheritdoc}
   */
  public function scanHandler(): ConnectorScanHandlerResultInterface {
    $this->scanner->setConnector($this);
    if ($this->scanner->scan()) {
      return new ConnectorScanHandlerResult([
        'projects' => $this->scanner->getProjectCount(),
        'releases' => $this->scanner->getReleaseCount(),
      ]);
    }
    return new ConnectorScanHandlerResult();
  }

}
