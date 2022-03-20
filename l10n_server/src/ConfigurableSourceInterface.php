<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\Core\Plugin\PluginWithFormsInterface;

/**
 * Defines the interface for a configurable l10n_server source.
 *
 * @see \Drupal\l10n_server\Annotation\Source
 * @see \Drupal\l10n_server\SourceManager
 * @see \Drupal\l10n_server\ConfigurableSourcePluginBase
 * @see plugin_api
 */
interface ConfigurableSourceInterface extends SourceInterface, ConfigurableInterface, PluginFormInterface, PluginWithFormsInterface, ContainerFactoryPluginInterface {

}
