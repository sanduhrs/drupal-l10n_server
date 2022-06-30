<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use function array_search;
use function array_unique;

/**
 * Connector manager class.
 */
final class ConnectorManager extends DefaultPluginManager implements ConnectorManagerInterface {

  /**
   * Config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected Config $editableConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(
      \Traversable $namespaces,
      CacheBackendInterface $cache_backend,
      ModuleHandlerInterface $module_handler,
      ConfigFactory $config_factory
  ) {
    parent::__construct(
      'Plugin/l10n_server/Connector',
      $namespaces,
      $module_handler,
      'Drupal\l10n_server\ConnectorInterface',
      'Drupal\l10n_server\Annotation\Connector'
    );
    $this->alterInfo('l10n_server_connector_info');
    $this->setCacheBackend($cache_backend, 'l10n_server_connector_info_plugins');
    $this->editableConfig = $config_factory->getEditable('l10n_server.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsList(): array {
    $options = [];
    foreach ($this->getDefinitions() as $id => $definition) {
      /** @var \Drupal\l10n_server\ConnectorInterface $plugin */
      $plugin = $this->createInstance($id);
      if ($plugin->isEnabled()) {
        $options[$plugin->getPluginId()] = $plugin->getLabel();
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setConnectorPluginStatus(string $module, bool $status = TRUE): void {
    $enabled_connectors = (array) $this->editableConfig->get('enabled_connectors');

    foreach ($this->getDefinitions() as $id => $definition) {
      /** @var \Drupal\l10n_server\ConnectorInterface $plugin */
      $plugin = $this->createInstance($id);
      $provider = $plugin->getPluginDefinition()['provider'];
      if ($module === $provider) {
        if ($status) {
          $enabled_connectors[] = $plugin->getPluginId();
        }
        else {
          $index = array_search($plugin->getPluginId(), $enabled_connectors, TRUE);
          unset($enabled_connectors[$index]);
        }
        $enabled_connectors = array_unique($enabled_connectors);
        $this->editableConfig->set('enabled_connectors', $enabled_connectors)->save(TRUE);
      }
    }
  }

  /**
   * Remove multiple plugin configuration.
   *
   * @param array $plugin_ids
   *   The plugin_id strings.
   */
  public function removePluginConfigurationMultiple(array $plugin_ids): void {
    $connectors = $this->editableConfig->get('connectors');
    foreach ($plugin_ids as $plugin_id) {
      unset($connectors[$plugin_id]);
    }
    $this->editableConfig->set('connectors', $connectors)->save();
  }

  /**
   * Remove plugin configuration.
   *
   * @param string $plugin_id
   *   The plugin_id string.
   */
  public function removePluginConfiguration(string $plugin_id): void {
    $this->removePluginConfigurationMultiple([$plugin_id]);
  }

}
