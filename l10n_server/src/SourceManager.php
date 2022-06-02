<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Source manager class.
 */
class SourceManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(
      \Traversable $namespaces,
      CacheBackendInterface $cache_backend,
      ModuleHandlerInterface $module_handler
  ) {
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

}
