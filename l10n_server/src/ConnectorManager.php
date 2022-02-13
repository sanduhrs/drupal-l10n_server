<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use function array_search;
use function array_unique;
use function in_array;

class ConnectorManager extends DefaultPluginManager implements ConnectorManagerInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/l10n_server/Connector',
      $namespaces,
      $module_handler,
      'Drupal\l10n_server\ConnectorInterface',
      'Drupal\l10n_server\Annotation\Connector'
    );
    $this->alterInfo('l10n_server_connector_info');
    $this->setCacheBackend($cache_backend, 'l10n_server_connector_info_plugins');
  }

  /**
   * {@inheritdoc}
   */
  public function getOptionsList(): array {
    $options = [];
    $enabled_connectors = (array) \Drupal::config('l10n_server.settings')
      ->get('enabled_connectors');
    foreach ($this->getDefinitions() as $id => $definition) {
      /** @var \Drupal\l10n_server\ConnectorInterface $plugin */
      $plugin = $this->createInstance($id);
      if (in_array('upload', $plugin->getSources()) && in_array($plugin->getPluginId(), $enabled_connectors)) {
        $options[$plugin->getPluginId()] = $plugin->getLabel();
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function setConnectorPluginStatus(string $module, bool $status = TRUE): void {
    $config = \Drupal::configFactory()->getEditable('l10n_server.settings');
    $enabled_connectors = (array) $config->get('enabled_connectors');

    foreach ($this->getDefinitions() as $id => $definition) {
      /** @var \Drupal\l10n_server\ConnectorInterface $plugin */
      $plugin = $this->createInstance($id);
      $provider = $plugin->getPluginDefinition()['provider'];
      if ($module === $provider) {
        if ($status) {
          $enabled_connectors[] = $plugin->getPluginId();
        }
        else {
          $index = array_search($plugin->getPluginId(), $enabled_connectors);
          unset($enabled_connectors[$index]);
        }
        $enabled_connectors = array_unique($enabled_connectors);
        $config->set('enabled_connectors', $enabled_connectors)->save(TRUE);
      }
    }
  }
}
