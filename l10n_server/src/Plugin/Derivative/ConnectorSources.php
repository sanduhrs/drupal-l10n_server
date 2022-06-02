<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\l10n_server\SourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Connector source class.
 */
class ConnectorSources extends DeriverBase implements ContainerDeriverInterface {

  /**
   * Source manager.
   *
   * @var \Drupal\l10n_server\SourceManager
   */
  protected SourceManager $sourceManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\l10n_server\SourceManager $sourceManager
   *   A source manager.
   */
  public function __construct(SourceManager $sourceManager) {
    $this->sourceManager = $sourceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id): self {
    return new static($container->get('plugin.manager.l10n_server.source'));
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
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
