<?php
declare(strict_types=1);

namespace Drupal\l10n_server\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\l10n_server\ConnectorManager;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a l10n_connector plugins.
 *
 * Example:
 *
 * @code
 * pattern: '/some/{connector}'
 * options:
 *   parameters:
 *     connector:
 *       type: 'l10n_connector'
 * @endcode
 *
 * The value for {connector} will be converted to a connector plugin instance.
 */
class L10nServerConnectorParamConverter implements ParamConverterInterface {

  /**
   * Connector plugin manager.
   *
   * @var \Drupal\l10n_server\ConnectorManager
   */
  protected ConnectorManager $connectorPluginManager;

  /**
   * Class constructor.
   *
   * @param \Drupal\l10n_server\ConnectorManager $connector_plugin_manager
   *   Connector plugin manager.
   */
  public function __construct(
      ConnectorManager $connector_plugin_manager
  ) {
    $this->connectorPluginManager = $connector_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($value)) {
      return $this->connectorPluginManager->hasDefinition($value)
        ? $this->connectorPluginManager->createInstance($value)
        : NULL;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route): bool {
    return !empty($definition['type'])
      && $definition['type'] === 'l10n_server_connector_plugin';
  }

}
