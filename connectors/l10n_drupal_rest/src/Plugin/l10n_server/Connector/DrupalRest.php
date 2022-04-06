<?php

declare(strict_types=1);

namespace Drupal\l10n_drupal\Plugin\l10n_server\Connector;

use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_drupal\Exception\MissingProjectCodeNameOrVersionNumberException;
use Drupal\l10n_server\ConfigurableConnectorPluginBase;
use Drupal\l10n_server\ConnnectorScanHandlerInterface;
use Drupal\l10n_server\Entity\Project;
use Drupal\l10n_server\Entity\ProjectInterface;
use Drupal\l10n_server\Entity\Release;
use Drupal\l10n_server\Entity\ReleaseInterface;
use Drupal\l10n_server\SourcePluginBase;
use function assert;
use function basename;
use function explode;
use function filemtime;
use function reset;
use function sprintf;

/**
 * A plugin to use source code of drupal package.
 *
 * @Connector(
 *   id = "drupal",
 *   label = @Translation("Drupal packages"),
 *   deriver = "Drupal\l10n_server\Plugin\Derivative\ConnectorSources",
 *   supported_sources = {
 *    "restapi"
 *   }
 * )
 */
class DrupalRest extends SourcePluginBase implements ConnnectorScanHandlerInterface {

}
