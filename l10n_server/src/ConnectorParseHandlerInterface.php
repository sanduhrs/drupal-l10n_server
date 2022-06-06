<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\l10n_server\Entity\L10nServerRelease;

/**
 * Connector parse handler interface.
 */
interface ConnectorParseHandlerInterface {

  /**
   * Sets the release.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerRelease $release
   *   The release object to set.
   *
   * @return $this
   */
  public function setRelease(L10nServerRelease $release): self;

  /**
   * Gets the release.
   *
   * @return \Drupal\l10n_server\Entity\L10nServerRelease
   *   The release object.
   */
  public function getRelease(): L10nServerRelease;

  /**
   * Parse handler.
   *
   * @return \Drupal\l10n_server\ConnectorParseHandlerResultInterface|false
   *   The parsing results object or false in case of error.
   */
  public function parseHandler(): ConnectorParseHandlerResultInterface|FALSE;

}
