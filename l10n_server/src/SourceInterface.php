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
   * Gets plugin label.
   *
   * @return string
   *   The label string.
   */
  public function getLabel(): string;

  /**
   * Gets plugin description.
   *
   * @return string
   *   The description string.
   */
  public function getDescription(): string;

  /**
   * Gets scan limit.
   *
   * @return int
   *   The scan limit integer.
   */
  public function getScanLimit(): int;

  /**
   * Is scanning on cron enabled?
   *
   * @return bool
   *   Whether this source should be executed during cron.
   */
  public function isCronScanningEnabled(): bool;

}
