<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Exception;

class SourceDoesNotSupportScan extends \UnexpectedValueException {
  protected $message = 'Source plugin does not support scanning';
}
