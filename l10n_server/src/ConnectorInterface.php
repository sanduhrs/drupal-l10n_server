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
   * Gets label.
   *
   * @return string
   *   The label string.
   */
  public function getLabel(): string;

  /**
   * Gets description.
   *
   * @return string
   *   The description string.
   */
  public function getDescription(): string;

  /**
   * Gets a source instance.
   *
   * @return \Drupal\l10n_server\SourceInterface
   *   The source object.
   */
  public function getSourceInstance(): SourceInterface;

  /**
   * Is connector enabled?
   */
  public function isEnabled(): bool;

  /**
   * Is source scannable?
   */
  public function isScannable(): bool;

  /**
   * Is source parseable?
   */
  public function isParsable(): bool;

}
