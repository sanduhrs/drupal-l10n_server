<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a source annotation object.
 *
 * Plugin Namespace: Plugin/l10n_server/Source.
 *
 * @see \Drupal\l10n_server\SourceInterface
 * @see \Drupal\l10n_server\SourceManager
 * @see \Drupal\l10n_server\SourcePluginBase
 * @see plugin_api
 *
 * @Annotation
 */
class Source extends Plugin {

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

}
