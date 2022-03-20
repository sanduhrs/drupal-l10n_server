<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class SourcePluginBase extends PluginBase implements SourceInterface {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) ($this->pluginDefinition['label'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) ($this->pluginDefinition['description'] ?? '');
  }

  /**
   * {@inheritdoc}
   */
  public function supportScan(): bool {
    // Lets the plugin decide.
    return FALSE;
  }

}
