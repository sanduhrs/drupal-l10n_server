<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;

class ConnectorSources extends DeriverBase implements ContainerDeriverInterface {

  /**
   * @var \Drupal\l10n_server\SourceManager
   */
  protected $sourceManager;

  /**
   * @param \Drupal\l10n_server\SourceManager $sourceManager
   */
  public function __construct(\Drupal\l10n_server\SourceManager $sourceManager) {
    $this->sourceManager = $sourceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container, $base_plugin_id) {
    return new static($container->get('plugin.manager.l10n_server.source'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($base_plugin_definition['supported_sources'] as $source_plugin_id) {
      if (!$this->sourceManager->hasDefinition($source_plugin_id)) {
        throw new PluginNotFoundException($source_plugin_id);
      }
      $this->derivatives[$source_plugin_id] = $base_plugin_definition;
      $this->derivatives[$source_plugin_id]['source_plugin_id'] = $source_plugin_id;
    }
    return parent::getDerivativeDefinitions($base_plugin_definition);
  }

}
