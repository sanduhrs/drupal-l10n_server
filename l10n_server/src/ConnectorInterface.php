<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Plugin\DerivativeInspectionInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;

/**
 * Defines the interface for a l10n_server connector.
 *
 * @see \Drupal\l10n_server\Annotation\Source
 * @see \Drupal\l10n_server\SourceManager
 * @see plugin_api
 */
interface ConnectorInterface extends PluginInspectionInterface, DerivativeInspectionInterface, ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string;

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string;

  /**
   * List of l10n_server source plugin ids.
   *
   * @return string[]
   */
  public function getSources(): array;

  /**
   * @return \Drupal\l10n_server\SourceInterface
   */
  public function getSourceInstance(): SourceInterface;

  /**
   * Connector is enabled or not.
   */
  public function isEnabled(): bool;

}
