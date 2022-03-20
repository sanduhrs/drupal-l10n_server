<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_intersect_key;

abstract class ConfigurableConnectorPluginBase extends ConnectorPluginBase implements ConfigurableConnectorInterface {

  use PluginWithFormsTrait;

  /**
   * The l10n_server connector service.
   *
   * @var \Drupal\l10n_server\ConnectorManager
   */
  protected $connectorManager;

  /**
   * @var \Drupal\l10n_server\ConnectorInterface
   */
  protected $connector;

  /**
   * The configuration factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   */
  public function setConfigFactory(ConfigFactoryInterface $configFactory): void {
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConfigFactory($container->get('config.factory'));
    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    foreach (array_intersect_key($form_state->getValues(), $this->configuration) as $config_key => $config_value) {
      $this->configuration[$config_key] = $config_value;
    }
  }

}
