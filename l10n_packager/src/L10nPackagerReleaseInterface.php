<?php

namespace Drupal\l10n_packager;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a packager release entity type.
 */
interface L10nPackagerReleaseInterface extends ContentEntityInterface, EntityChangedInterface {

  public function getCheckedTime(): int;

}
