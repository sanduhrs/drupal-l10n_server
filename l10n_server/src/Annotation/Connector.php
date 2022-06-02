<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines connector annotation object.
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
   * The plugin id.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the connector.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public Translation $label;

  /**
   * List of source plugin ids.
   *
   * @var string[]
   */
  public array $supported_sources;

}
