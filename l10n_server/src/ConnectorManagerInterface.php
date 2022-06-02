<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

/**
 * Connector manager interface.
 */
interface ConnectorManagerInterface {

  /**
   * List of connector plugins.
   *
   * @return string[]
   *   An array of strings.
   */
  public function getOptionsList(): array;

  /**
   * Enables/Disables all connectors from a module.
   *
   * @param string $module
   *   The module name.
   * @param bool $status
   *   The connector status.
   */
  public function setConnectorPluginStatus(string $module, bool $status = TRUE): void;

}
