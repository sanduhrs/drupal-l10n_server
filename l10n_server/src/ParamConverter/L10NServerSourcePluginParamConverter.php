<?php
declare(strict_types=1);

namespace Drupal\l10n_server\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\l10n_server\SourceManager;
use Symfony\Component\Routing\Route;

/**
 * Provides upcasting for a l10n_server source.
 *
 * Example:
 *
 * pattern: '/some/{l10n_server_source_plugin}'
 *
 * The value for {l10n_server_source_plugin} will be converted to a source plugin instance.
 */
class L10NServerSourcePluginParamConverter implements ParamConverterInterface {

  /**
   * @var \Drupal\l10n_server\SourceManager
   */
  protected $sourceManager;

  /**
   * @param \Drupal\l10n_server\SourceManager $sourceManager
   */
  public function __construct(SourceManager $sourceManager) {
    $this->sourceManager = $sourceManager;
  }

  /**
   * {@inheritdoc}
   */
  public function convert($value, $definition, $name, array $defaults) {
    if (!empty($value)) {
      return $this->sourceManager->hasDefinition($value) ? $this->sourceManager->createInstance($value, $this->sourceManager->getPluginConfiguration($value)) : NULL;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function applies($definition, $name, Route $route) {
    return !empty($definition['type']) && $definition['type'] === 'l10n_server_source_plugin';
  }

}
