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
}
