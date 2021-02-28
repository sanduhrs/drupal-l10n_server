<?php
declare(strict_types=1);

namespace Drupal\l10n_drupal\Plugin\l10n_server\Connector;

use Drupal\l10n_server\ConnectorPluginBase;

/**
 * A plugin to use source code of drupal package.
 *
 * @Connector(
 *   id = "drupal",
 *   label = @Translation("Drupal packages"),
 *   description = @Translation("Drupal packages from the file system"),
 *   supported_sources = {
 *    "filesystem",
 *    "uploads",
 *   }
 * )
 */
class Drupal extends ConnectorPluginBase {

}
