<?php
declare(strict_types=1);

namespace Drupal\l10n_gettext\Plugin\l10n_server\Connector;

use Drupal\l10n_server\ConnectorPluginBase;

/**
 * A plugin to use pot files.
 *
 * @Connector(
 *   id = "gettext",
 *   label = @Translation("Gettext files"),
 *   description = @Translation("Drupal packages from the file system"),
 *   supported_sources = {
 *    "uploads",
 *   }
 * )
 */
class GetText extends ConnectorPluginBase {

}
