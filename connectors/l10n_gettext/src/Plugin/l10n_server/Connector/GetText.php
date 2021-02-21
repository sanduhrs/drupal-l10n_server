<?php

namespace Drupal\l10n_gettext\Plugin\l10n_server\Connector;

use Drupal\l10n_server\ConnectorPluginBase;

/**
 * Checks if a user name is unique on the site.
 *
 * @Connector(
 *   id = "gettext",
 *   label = @Translation("Gettext files"),
 *   description = @Translation("Drupal packages from the file system"),
 *   sources = {
 *    "uploads",
 *   }
 * )
 */
class GetText extends ConnectorPluginBase {

}
