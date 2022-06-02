<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\Core\Plugin\PluginFormFactoryInterface;
use Drupal\l10n_server\ConnectorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function assert;

/**
 * Form class.
 */
class ConnectorConfiguration extends ConfigFormBase {

  /**
   * The plugin form factory.
   *
   * @var \Drupal\Core\Plugin\PluginFormFactoryInterface
   */
  protected PluginFormFactoryInterface $pluginFormFactory;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
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
  public function buildForm(array $form, FormStateInterface $form_state, ?ConnectorInterface $connector = NULL): array {
    $form = parent::buildForm($form, $form_state);
    $form['#tree'] = TRUE;
    assert($connector instanceof ConnectorInterface);
    $form_state->set('connector', $connector);

    if ($connector->isConfigurable()) {
      $form['connector'] = [
        '#type' => 'details',
        '#title' => $this->t('@plugin_label configuration', [
          '@plugin_label' => $connector->getLabel(),
        ]),
        '#open' => TRUE,
      ];
      $subform_state = SubformState::createForSubform($form['connector'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableConnectorInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($connector, 'configure');
      $form['connector'] = $instance->buildConfigurationForm($form['connector'], $subform_state);
    }

    $source = $connector->getSourceInstance();
    if ($source->isConfigurable()) {
      $form['source'] = [
        '#type' => 'details',
        '#title' => $this->t('@plugin_label configuration', [
          '@plugin_label' => $source->getLabel(),
        ]),
        '#open' => TRUE,
      ];
      $subform_state = SubformState::createForSubform($form['source'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableSourceInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($source, 'configure');
      $form['source'] = $instance->buildConfigurationForm($form['source'], $subform_state);
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    parent::validateForm($form, $form_state);
    $connector = $form_state->get('connector');

    if ($connector->isConfigurable()) {
      $subform_state = SubformState::createForSubform($form['connector'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableConnectorInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($connector, 'configure');
      $instance->validateConfigurationForm($form['connector'], $subform_state);
    }

    $source = $connector->getSourceInstance();
    if ($source->isConfigurable()) {
      $subform_state = SubformState::createForSubform($form['source'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableSourceInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($source, 'configure');
      $instance->validateConfigurationForm($form['source'], $subform_state);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    parent::submitForm($form, $form_state);
    $form_state->setRedirect('l10n_server.connectors');

    $config = $this->config('l10n_server.settings')->getRawData();
    $connector = $form_state->get('connector');
    $connector_config = $config['connectors'][$connector->getPluginId()] ?? [];

    if ($connector->isConfigurable()) {
      $subform_state = SubformState::createForSubform($form['connector'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableConnectorInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($connector, 'configure');
      $instance->submitConfigurationForm($form['connector'], $subform_state);
      $connector_config['connector'][$connector->getBaseId()] = $instance->getConfiguration();
    }

    $source = $connector->getSourceInstance();
    if ($source->isConfigurable()) {
      $subform_state = SubformState::createForSubform($form['source'], $form, $form_state);
      /** @var \Drupal\l10n_server\ConfigurableSourceInterface $instance */
      $instance = $this->pluginFormFactory->createInstance($source, 'configure');
      $instance->submitConfigurationForm($form['source'], $subform_state);
      $connector_config['source'][$connector->getDerivativeId()] = $instance->getConfiguration();
    }

    if ($connector_config !== []) {
      $config['connectors'][$connector->getPluginId()] = $connector_config;
      $this->config('l10n_server.settings')->setData($config)->save();
    }
  }

}
