<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function in_array;

abstract class ConnectorPluginBase extends PluginBase implements ConnectorInterface {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @var \Drupal\l10n_server\SourceManager
   */
  protected $sourceManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->configFactory = $container->get('config.factory');
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
  public function getSources(): array {
    return $this->pluginDefinition['supported_sources'];
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


}
