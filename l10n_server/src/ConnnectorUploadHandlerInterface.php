<?php

declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\file\FileInterface;

interface ConnnectorUploadHandlerInterface {
  public static function uploadHandler(FileInterface $file);
  public function getUploadValidators(): array;
}
