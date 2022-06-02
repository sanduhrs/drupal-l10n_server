<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\l10n_server\Entity\L10nServerReleaseInterface;

/**
 * Connector parse handler interface.
 */
interface ConnectorParseHandlerInterface {

  /**
   * Parse handler.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerReleaseInterface $release
   *   The release object.
   *
   * @return \Drupal\l10n_server\ConnectorParseHandlerResultInterface
   *   The parsing results object.
   */
  public function parseHandler(L10nServerReleaseInterface $release): ConnectorParseHandlerResultInterface;

}
