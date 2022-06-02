<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

/**
 * Connector scan handler interface.
 */
interface ConnectorScanHandlerInterface {

  /**
   * Scan handler.
   *
   * @return \Drupal\l10n_server\ConnectorScanHandlerResultInterface
   *   The scan handler result object.
   */
  public function scanHandler(): ConnectorScanHandlerResultInterface;

}
