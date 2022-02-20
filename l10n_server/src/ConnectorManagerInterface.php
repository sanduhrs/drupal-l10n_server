<?php

declare(strict_types=1);

namespace Drupal\l10n_server;

interface ConnectorManagerInterface {

  /**
   * List of connector plugins.
   *
   * @return string[]
   */
  public function getOptionsList(): array;

  /**
   * Enables/Disables all connectors from a module.
   *
   * @param string $module
   * @param bool $status
   *
   * @return void
   */
  public function setConnectorPluginStatus(string $module, bool $status = TRUE): void;
}
