<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\l10n_server\Source;

use Drupal\l10n_server\ConfigurableSourcePluginBase;

/**
 * Source plugin to retrieve releases from drupal.org.
 *
 * @Source(
 *   id = "upload",
 *   label = @Translation("Upload"),
 *   description = @Translation("Retrieve from file uploads"),
 * )
 */
final class Upload extends ConfigurableSourcePluginBase {

}
