<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\DependentPluginInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ConfigurableSourcePluginBase extends SourcePluginBase implements ConfigurableInterface, PluginFormInterface, DependentPluginInterface, ContainerFactoryPluginInterface {

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
   * @param \Drupal\l10n_server\ConnectorManager $connectorManager
   */
  public function setConnectorManager(ConnectorManager $connectorManager): void {
    $this->connectorManager = $connectorManager;
  }

  public function setConnector(ConnectorInterface $connector) {
    $this->connector = $connector;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static($configuration, $plugin_id, $plugin_definition);
    $instance->setConnectorManager($container->get('plugin.manager.l10n_server.connector'));
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
    $this->configuration = array_merge($this->defaultConfiguration(), $configuration);
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    $this->setConfiguration($form_state->getValues());
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }
}
