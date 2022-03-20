<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\l10n_server\ConfigurableConnectorInterface;
use Drupal\l10n_server\ConfigurableSourceInterface;
use Drupal\l10n_server\ConnectorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function assert;

class ConnectorConfiguration extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected $pluginFormFactory;

  /**
   * @inheritDoc
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->pluginFormFactory = $container->get('plugin_form.factory');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'l10n_server_connector_configuration';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'l10n_server.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ?ConnectorInterface $connector = NULL) {
    $form = parent::buildForm($form, $form_state);
    $form['#tree'] = TRUE;
    assert($connector instanceof ConnectorInterface);
    $form_state->set('connector', $connector);

    if ($connector instanceof ConfigurableConnectorInterface) {
      $form['connector'] = [
        '#type' => 'details',
        '#title' => $this->t('@plugin_id configuration', ['@plugin_id' => $connector->getLabel()]),
        '#open' => TRUE,
      ];
      $subformState = SubformState::createForSubform($form['connector'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableConnectorInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($connector, 'configure');
      $form['connector'] = $instance->buildConfigurationForm($form['connector'], $subformState);
    }

    $source_plugin = $connector->getSourceInstance();
    if ($source_plugin instanceof ConfigurableSourceInterface) {
      $form['source'] = [
        '#type' => 'details',
        '#title' => $this->t('@plugin_id configuration', ['@plugin_id' => $source_plugin->getLabel()]),
        '#open' => TRUE,
      ];
      $subformState = SubformState::createForSubform($form['source'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableSourceInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($source_plugin, 'configure');
      $form['source'] = $instance->buildConfigurationForm($form['source'], $subformState);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    $connector = $form_state->get('connector');
    if ($connector instanceof ConfigurableConnectorInterface) {
      $subformState = SubformState::createForSubform($form['connector'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableConnectorInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($connector, 'configure');
      $instance->validateConfigurationForm($form['connector'], $subformState);
    }

    $source_plugin = $connector->getSourceInstance();
    if ($source_plugin instanceof ConfigurableSourceInterface) {
      $subformState = SubformState::createForSubform($form['source'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableSourceInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($source_plugin, 'configure');
      $instance->validateConfigurationForm($form['source'], $subformState);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $connector = $form_state->get('connector');

    $config = $this->config('l10n_server.settings')->getRawData();
    $connector_config = $config['connectors'][$connector->getPluginId()] ?? [];

    if ($connector instanceof ConfigurableConnectorInterface) {
      $subformState = SubformState::createForSubform($form['connector'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableConnectorInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($connector, 'configure');
      $instance->submitConfigurationForm($form['connector'], $subformState);
      $connector_config['connector'][$connector->getBaseId()] = $instance->getConfiguration();
    }

    $source_plugin = $connector->getSourceInstance();
    if ($source_plugin instanceof ConfigurableSourceInterface) {
      $subformState = SubformState::createForSubform($form['source'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableSourceInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($source_plugin, 'configure');
      $instance->submitConfigurationForm($form['source'], $subformState);
      $connector_config['source'][$connector->getDerivativeId()] = $instance->getConfiguration();
    }
    if ($connector_config !== []) {
      $config['connectors'][$connector->getPluginId()] = $connector_config;
      $this->config('l10n_server.settings')->setData($config)->save();
    }

  }

}
