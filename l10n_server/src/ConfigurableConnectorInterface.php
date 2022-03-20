<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Defines the interface for a configurable l10n_server connector.
 *
 * @see \Drupal\l10n_server\Annotation\Connector
 * @see \Drupal\l10n_server\ConnectorManagerManager
 * @see \Drupal\l10n_server\ConfigurableConnectorPluginBase
 * @see plugin_api
 */
interface ConfigurableConnectorInterface extends ConnectorInterface, ConfigurableInterface, PluginFormInterface, PluginWithFormsInterface {

}
