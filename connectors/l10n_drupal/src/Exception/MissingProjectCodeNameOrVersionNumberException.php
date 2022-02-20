<?php

declare(strict_types=1);

namespace Drupal\l10n_drupal\Exception;

class MissingProjectCodeNameOrVersionNumberException extends \UnexpectedValueException {
  protected $message = 'File name should have project codename and version number included separated with hyphen, such as drupal-5.2.tar.gz.';
}
