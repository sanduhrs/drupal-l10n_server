<?php

namespace Drupal\l10n_gettext\Plugin\l10n_server\Connector;

use Drupal\l10n_server\ConnectorParseHandlerInterface;
use Drupal\l10n_server\ConnectorParseHandlerResult;
use Drupal\l10n_server\ConnectorParseHandlerResultInterface;
use Drupal\l10n_server\ConnectorPluginBase;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;

/**
 * A plugin to use source code of drupal.org package.
 *
 * @Connector(
 *   id = "gettext",
 *   label = @Translation("Gettext packages"),
 *   deriver = "Drupal\l10n_server\Plugin\Derivative\ConnectorSources",
 *   supported_sources = {
 *     "restapi",
 *   }
 * )
 */
class Gettext extends ConnectorPluginBase implements ConnectorParseHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function parseHandler(L10nServerReleaseInterface $release = NULL): ConnectorParseHandlerResultInterface {
    // @todo Fix parser handling.
    return new ConnectorParseHandlerResult([
      'files' => rand(0, 9),
      'lines' => rand(0, 99),
      'strings' => rand(0, 999),
    ]);
  }

}
