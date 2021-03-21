<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

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
    /** @var \Drupal\l10n_server\ConnectorInterface $definition */
    foreach ($this->getDefinitions() as $id => $definition) {
      $plugin = $this->createInstance($id);
      $options[$plugin->getPluginId()] = $plugin->getLabel();
    }
    return $options;
  }
}
