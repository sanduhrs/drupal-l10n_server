<?php

namespace Drupal\l10n_server;

use Drupal\Component\Plugin\PluginInspectionInterface;

/**
 * Defines the interface for matchers.
 *
 * @see \Drupal\l10n_server\Annotation\Connector
 * @see \Drupal\l10n_server\ConnectorManager
 * @see plugin_api
 */
interface ConnectorInterface extends PluginInspectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel(): string;

  /**
   * {@inheritdoc}
   */
  public function getDescription(): string;

  /**
   * Supported sources.
   *
   * @return array
   */
  public function getSources(): array;

}
