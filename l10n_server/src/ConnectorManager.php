<?php

declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use function array_search;
use function array_unique;
use Traversable;

final class ConnectorManager extends DefaultPluginManager implements ConnectorManagerInterface {

  /**
   * @var \Drupal\Core\Config\Config
   */
  protected $editableConfig;

  /**
   * {@inheritdoc}
   */
  public function __construct(Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/l10n_server/Connector',
      $namespaces,
      $module_handler,
      'Drupal\l10n_server\ConnectorInterface',
      'Drupal\l10n_server\Annotation\Connector'
    );
    $this->alterInfo('l10n_server_connector_info');
    $this->setCacheBackend($cache_backend, 'l10n_server_connector_info_plugins');
    $this->editableConfig = \Drupal::configFactory()->getEditable('l10n_server.settings');
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

  public function removePluginConfigurationMultiple(array $plugin_ids) {
    $connectors = $this->editableConfig->get('connectors');
    foreach ($plugin_ids as $plugin_id) {
      unset($connectors[$plugin_id]);
    }
    $this->editableConfig->set('connectors', $connectors)->save();
  }

  public function removePluginConfiguration(string $plugin_id) {
    $this->removePluginConfigurationMultiple([$plugin_id]);
  }

}
