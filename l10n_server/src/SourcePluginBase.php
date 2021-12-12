<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Plugin\PluginBase;

abstract class SourcePluginBase extends PluginBase implements SourceInterface {

  /**
   * @var \Drupal\l10n_server\ConnectorInterface
   */
  protected $connector;

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return $this->pluginDefinition['label'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return $this->pluginDefinition['description'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function supportScan(): bool {
    // Lets the plugin decide.
    return FALSE;
  }

}
