<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_server\ConfigurableSourcePluginBase;
use Drupal\l10n_server\ConnectorInterface;

class ConnectorConfiguration extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'l10n_server_connector_configuration';
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
  public function buildForm(array $form, FormStateInterface $form_state, ?ConnectorInterface $connector = NULL, ?ConfigurableSourcePluginBase $source = NULL) {
    assert($source instanceof ConfigurableSourcePluginBase);
    assert($connector instanceof ConnectorInterface);
    $form_state->set('connector', $connector);
    $form_state->set('source', $source);
    $form = $source->buildConfigurationForm($form, $form_state);
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $source = $form_state->get('source');
    assert($source instanceof ConfigurableSourcePluginBase);
    $source->validateConfigurationForm($form, $form_state);
    parent::validateForm($form, $form_state);
  }


  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $source = $form_state->get('source');
    assert($source instanceof ConfigurableSourcePluginBase);
    $connector = $form_state->get('connector');
    assert($connector instanceof ConnectorInterface);
    $source->submitConfigurationForm($form, $form_state);
    $this->config('l10n_server.settings')->set($source->getPluginId(), $source->getConfiguration())
      ->set('dependencies', $connector->calculateDependencies())->save();
  }

}
