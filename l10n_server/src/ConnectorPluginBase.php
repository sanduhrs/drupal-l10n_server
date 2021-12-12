<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Core\Plugin\PluginBase;

abstract class ConnectorPluginBase extends PluginBase implements ConnectorInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string {
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string {
    return (string) $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function getSources(): array {
    return $this->pluginDefinition['supported_sources'];
  }

  /**
   * @inheritDoc
   */
  public function calculateDependencies() {
    $modules = NULL;
    /** @var \Drupal\l10n_server\SourceManager $manager */
    $manager = \Drupal::service('plugin.manager.l10n_server.source');
    foreach ($this->getSources() as $plugin_id) {
      if ($manager->hasDefinition($plugin_id)) {
        /** @var \Drupal\l10n_server\SourceInterface $source */
        $source = $manager->createInstance($plugin_id);
        $provider = $source->getPluginDefinition()['provider'];
        if ($provider !== 'l10n_server') {
          $modules[] = $source->getPluginDefinition()['provider'];
        }
      }
    }

    if ($modules) {
      return ['modules' => $modules];
    }
    return [];
  }


}
