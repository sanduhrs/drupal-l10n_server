<?php
declare(strict_types=1);

namespace Drupal\l10n_server\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\l10n_server\ConnectorManager;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a l10n_server connector.
 *
 * Example:
 *
 * @code
 * pattern: '/some/{connector}'
 * options:
 *   parameters:
 *     connector:
 *       type: 'l10n_server_source_plugin'
 * @endcode
 *
 * The value for {connector} will be converted to a connector plugin instance.
 */
class L10NServerConnectorPluginParamConverter implements ParamConverterInterface {

  /** @var \Drupal\l10n_server\ConnectorManager */
  protected $connectorManager;

  /**
   * @param \Drupal\l10n_server\ConnectorManager $pluginManager
   */
  public function __construct(ConnectorManager $connectorManager) {
    $this->connectorManager = $connectorManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($value)) {
      return $this->connectorManager->hasDefinition($value) ? $this->connectorManager->createInstance($value) : NULL;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] === 'l10n_server_connector_plugin';
  }

}
