<?php

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_server\ConnectorInterface;
use Drupal\l10n_server\ConnectorManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures devel settings.
 */
final class SettingsForm extends ConfigFormBase {

  /** @var \Drupal\l10n_server\ConnectorManager */
  protected $connectorManager;

  /**
   * @param \Drupal\l10n_server\ConnectorManager $pluginManager
   */
  public function setConnectorManager(ConnectorManager $connectorManager): void {
    $this->connectorManager = $connectorManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setConnectorManager($container->get('plugin.manager.l10n_server_connector'));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'l10n_server_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'l10n_server.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $header = [
      'connector' => ['data' => $this->t('Connector'), 'colspan' => 2],
    ];

    $settings = $this->config('l10n_server.settings');
    $enabled_connectors = $settings->get('enabled_connectors') ?? [];

    $connectors = $this->connectorManager->getDefinitions();
    $options = [];
    foreach ($connectors as $id => $definition) {
      if (!$this->connectorManager->hasDefinition($id)) {
        continue;
      }
      $connector = $this->connectorManager->createInstance($id);
      assert($connector instanceof ConnectorInterface);
      $options[$connector->getPluginId()] = [
        'connector' => [$connector->getLabel(), $connector->getDescription()],
      ];
    }
    $form['connectors'] = array(
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => array_combine($enabled_connectors, $enabled_connectors),
      '#empty' => $this->t('No localization server connectors found.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * @inheritDoc
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = array_values(array_filter($form_state->getValue('connectors', [])));
    $this->config('l10n_server.settings')
      ->set('enabled_connectors', $submitted_values)
      ->save();
  }


}
