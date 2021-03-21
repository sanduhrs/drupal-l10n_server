<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

class SourceManager extends DefaultPluginManager {

  /**
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/l10n_server/Source',
      $namespaces,
      $module_handler,
      'Drupal\l10n_server\SourceInterface',
      'Drupal\l10n_server\Annotation\Source'
    );
    $this->alterInfo('l10n_server_source_info');
    $this->setCacheBackend($cache_backend, 'l10n_server_source_info_plugins');
  }

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function setConfigFactory(ConfigFactoryInterface $configFactory) {
    $this->configFactory = $configFactory;
  }

  public function getPluginConfiguration(string $plugin_id): array {
    return $this->configFactory->get('l10n_server.settings')->get($plugin_id) ?? [];
  }

}