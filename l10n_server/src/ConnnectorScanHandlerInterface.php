<?php

declare(strict_types=1);

namespace Drupal\l10n_server;

interface ConnnectorScanHandlerInterface {

  public function fileExtension(): string;

  /**
   * @param array $files
   *   An associative array (keyed on the chosen key) of objects with 'uri',
   *   'filename', and 'name' properties corresponding to the matched files.
   *
   * @return void
   */
  public function scanHandler(array $files, string $sourceDirectory): void;
}
