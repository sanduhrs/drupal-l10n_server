<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Annotation;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Defines source annotation object.
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
   * The plugin id.
   *
   * @var string
   */
  public string $id;

  /**
   * The human-readable name of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public Translation $label;

}
