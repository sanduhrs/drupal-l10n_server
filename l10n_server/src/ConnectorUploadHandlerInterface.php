<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\file\FileInterface;

/**
 * Connector upload handler interface.
 */
interface ConnectorUploadHandlerInterface {

  /**
   * Upload handler.
   *
   * @param \Drupal\file\FileInterface $file
   *   The file.
   */
  public static function uploadHandler(FileInterface $file): void;

  /**
   * Gets upload validators.
   *
   * @return array
   *   An array of upload validators.
   */
  public function getUploadValidators(): array;

}
