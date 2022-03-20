<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for a l10n_server source.
 *
 * @see \Drupal\l10n_server\Annotation\Connector
 * @see \Drupal\l10n_server\ConnectorManager
 * @see plugin_api
 */
interface SourceInterface extends PluginInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string;

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string;

  /**
   * Allows the user to trigger the plugin
   * to scan this source via link for new data.
   *
   * @return bool
   */
  public function supportScan(): bool;
}
