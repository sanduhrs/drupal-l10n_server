<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\l10n_server\ConfigurableConnectorInterface;
use Drupal\l10n_server\ConfigurableSourceInterface;
use Drupal\l10n_server\ConnectorInterface;
use Drupal\l10n_server\ConnectorManager;
use Drupal\l10n_server\SourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_diff;
use function array_intersect_key;
use function assert, array_values, array_combine, array_filter;

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
  public function getFormId(): string {
    return 'l10n_server_settings_form';
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
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $header = [
      'connector' => ['data' => $this->t('Connector'), 'colspan' => 1],
      'operations' => $this->t('Operations'),
    ];

    $settings = $this->config('l10n_server.settings');
    $enabled_connectors = $settings->get('enabled_connectors') ?? [];

    $connectors = $this->connectorManager->getDefinitions();
    $options = [];
    foreach ($connectors as $id => $definition) {
      $connector = $this->connectorManager->createInstance($id);
      assert($connector instanceof ConnectorInterface);
      $options[$connector->getPluginId()] = [
        'connector' => [$this->t('@title from @source', array('@title' => $connector->getLabel(), '@source' => $connector->getSourceInstance()->getLabel()))],
      ];
      if (!$connector->isEnabled()) {
        continue;
      }
      $links = NULL;
      $options[$connector->getPluginId()]['operations'] = ['data' => ['#type' => 'operations', '#links' => $links]];
      $source = $connector->getSourceInstance();
      if ($source instanceof ConfigurableSourceInterface || $connector instanceof ConfigurableConnectorInterface) {
        $links['configure'] = [
          'title' => $this->t('Configure'),
          'url' => Url::fromRoute('l10n_server.connector.configure', ['connector' => $connector->getPluginId()]),
        ];
      }
      if ($source->supportScan()) {
        $links['scan'] = [
          'title' => $this->t('Scan'),
          'url' => Url::fromRoute('l10n_server.connector.scan', ['connector' => $connector->getPluginId()]),
        ];
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
    $origin = $form_state->getValue('connectors', []);
    $enabled = array_values(array_filter($origin));
    $disabled = array_keys(array_diff($origin, array_filter($origin)));
    $this->connectorManager->removePluginConfigurationMultiple($disabled);
    $this->config('l10n_server.settings')
      ->set('enabled_connectors', $enabled)
      ->save();
  }

}
