<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function in_array;

/**
 * Connector plugin base class.
 */
abstract class ConnectorPluginBase extends PluginBase implements ConnectorInterface {

  /**
   * Config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Logger channel factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected LoggerChannelFactoryInterface $loggerFactory;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected LoggerChannelInterface $logger;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Source manager.
   *
   * @var \Drupal\l10n_server\SourceManager
   */
  protected SourceManager $sourceManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
    $instance->loggerFactory = $container->get('logger.factory');
    $instance->logger = $instance->loggerFactory->get('l10n_server');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->sourceManager = $container->get('plugin.manager.l10n_server.source');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSourceInstance(): SourceInterface {
    $settings = $this->configFactory->get('l10n_server.settings');
    $source_plugin_id = $this->pluginDefinition['source_plugin_id'];
    $source_config = $settings->get("connectors.{$this->pluginId}.source.$source_plugin_id") ?? [];
    /** @var \Drupal\l10n_server\SourceInterface $instance */
    $instance = $this->sourceManager->createInstance($source_plugin_id, $source_config);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled(): bool {
    $settings = $this->configFactory->get('l10n_server.settings');
    $enabled_connectors = $settings->get('enabled_connectors') ?? [];
    return in_array($this->getPluginId(), $enabled_connectors, TRUE);
  }

  /**
   * {@inheritdoc}
   */
  public function isParsable(): bool {
    return $this instanceof ConnectorParseHandlerInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function isScannable(): bool {
    return $this instanceof ConnectorScanHandlerInterface;
  }

}
