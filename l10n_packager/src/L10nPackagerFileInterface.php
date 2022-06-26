<?php
declare(strict_types=1);

namespace Drupal\l10n_packager;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a packager file entity type.
 */
interface L10nPackagerFileInterface extends ContentEntityInterface, EntityChangedInterface {

}
