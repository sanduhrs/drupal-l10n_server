<?php

namespace Drupal\l10n_server\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a connector annotation object.
 *
 * Plugin Namespace: Plugin/l10n_server/connector.
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
   * @var array
   */
  public $sources;
}
