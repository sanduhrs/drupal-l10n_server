<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\l10n_server\ConfigurableSourcePluginBase;
use Drupal\l10n_server\ConnectorInterface;
use Drupal\l10n_server\ConnectorManager;
use Drupal\l10n_server\SourceInterface;
use Drupal\l10n_server\SourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a form that configures devel settings.
 */
final class SettingsForm extends ConfigFormBase {

  /** @var \Drupal\l10n_server\ConnectorManager */
  protected $connectorManager;

  /** @var \Drupal\l10n_server\SourceManager */
  protected $sourceManager;

  /**
   * @param \Drupal\l10n_server\ConnectorManager $pluginManager
   */
  public function setConnectorManager(ConnectorManager $connectorManager): void {
    $this->connectorManager = $connectorManager;
  }

  /**
   * @param \Drupal\l10n_server\SourceManager $pluginManager
   */
  public function setSourceManager(SourceManager $sourceManager): void {
    $this->sourceManager = $sourceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->setConnectorManager($container->get('plugin.manager.l10n_server.connector'));
    $instance->setSourceManager($container->get('plugin.manager.l10n_server.source'));
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
      'operations' => $this->t('Operations'),
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
      if (!in_array($connector->getPluginId(), $enabled_connectors)) {
        continue;
      }
      $links = NULL;
      $options[$connector->getPluginId()]['operations'] = ['data' => ['#type' => 'operations', '#links' => $links]];
      foreach ($connector->getSources() as $source_plugin_id) {
        if (!$this->sourceManager->hasDefinition($source_plugin_id)) {
          continue;
        }
        $source = $this->sourceManager->createInstance($source_plugin_id);
        assert($source instanceof SourceInterface);
        if ($source->supportScan()) {
          $links['scan'] = [
            'title' => $this->t('Scan'),
            'url' => Url::fromRoute('l10n_server.connector.scan', ['connector' => $connector->getPluginId(), 'source' => $source->getPluginId()]),
          ];
        }
        if ($source instanceof ConfigurableSourcePluginBase) {
          /** SourceInterface&ConfigurableInterface $source */
          $links['configure'] = [
            'title' => $this->t('Configure'),
            'url' => Url::fromRoute('l10n_server.connector.configure', ['connector' => $connector->getPluginId(), 'source' => $source->getPluginId()]),
          ];
        }
      }
      if ($links) {
        $options[$connector->getPluginId()]['operations']['data']['#links'] = $links;
      }
      else {
        $options[$connector->getPluginId()]['operations'] = '';
      }
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $submitted_values = array_values(array_filter($form_state->getValue('connectors', [])));
    $this->config('l10n_server.settings')
      ->set('enabled_connectors', $submitted_values)
      ->save();
  }

}
