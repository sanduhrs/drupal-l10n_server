<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a connector annotation object.
 *
 * Plugin Namespace: Plugin/l10n_server/Connector.
 *
 * @see \Drupal\l10n_server\ConnectorInterface
 * @see \Drupal\l10n_server\ConnectorManager
 * @see \Drupal\l10n_server\ConnectorPluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class Connector extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the connector.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * List of l10n_server source plugin ids.
   *
   * @var string[]
   */
  public $supported_sources;
}
