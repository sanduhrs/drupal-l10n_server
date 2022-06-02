<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\l10n_server\ConnectorInterface;
use Drupal\l10n_server\ConnectorManager;
use Drupal\l10n_server\SourceManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_diff;
use function assert;
use function array_values;
use function array_combine;
use function array_filter;

/**
 * Defines a form that configures devel settings.
 */
final class L10nServerSettingsForm extends ConfigFormBase {

  /**
   * Connector manager.
   *
   * @var \Drupal\l10n_server\ConnectorManager
   */
  protected ConnectorManager $connectorManager;

  /**
   * Source manager.
   *
   * @var \Drupal\l10n_server\SourceManager
   */
  protected SourceManager $sourceManager;

  /**
   * Sets connector manager.
   *
   * @param \Drupal\l10n_server\ConnectorManager $connectorManager
   *   The connector manager.
   */
  public function setConnectorManager(ConnectorManager $connectorManager): void {
    $this->connectorManager = $connectorManager;
  }

  /**
   * Sets source manager.
   *
   * @param \Drupal\l10n_server\SourceManager $sourceManager
   *   The source manager.
   */
  public function setSourceManager(SourceManager $sourceManager): void {
    $this->sourceManager = $sourceManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
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
    $settings = $this->config('l10n_server.settings');
    $enabled_connectors = $settings->get('enabled_connectors');

    $connectors = $this->connectorManager->getDefinitions();
    $options = [];
    foreach ($connectors as $id => $definition) {
      $connector = $this->connectorManager->createInstance($id);
      assert($connector instanceof ConnectorInterface);
      $options[$connector->getPluginId()] = [
        'connector' => [
          $this->t('@title from @source', [
            '@title' => $connector->getLabel(),
            '@source' => $connector->getSourceInstance()->getLabel(),
          ]),
        ],
      ];

      if (!$connector->isEnabled()) {
        continue;
      }
      $links = NULL;
      $options[$connector->getPluginId()]['operations'] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];

      $source = $connector->getSourceInstance();
      // Add operation if the connector is configurable.
      if ($source->isConfigurable()) {
        $links['configure'] = [
          'title' => $this->t('Configure'),
          'url' => Url::fromRoute('l10n_server.connector.configure', [
            'connector' => $connector->getPluginId(),
          ]),
        ];
      }

      // Add operation if connector is scannable.
      if ($connector->isScannable()) {
        $links['scan'] = [
          'title' => $this->t('Scan'),
          'url' => Url::fromRoute('l10n_server.connector.batch_scan', [
            'connector' => $connector->getPluginId(),
          ]),
        ];
      }

      // Add operation if connector is parsable.
      if ($connector->isParsable()) {
        $links['parse'] = [
          'title' => $this->t('Parse'),
          'url' => Url::fromRoute('l10n_server.connector.batch_parse', [
            'connector' => $connector->getPluginId(),
          ]),
        ];
      }

      if ($links) {
        $options[$connector->getPluginId()]['operations']['data']['#links'] = $links;
      }
      else {
        $options[$connector->getPluginId()]['operations'] = '';
      }
    }

    $form['connectors'] = [
      '#type' => 'tableselect',
      '#header' => [
        'connector' => [
          'data' => $this->t('Connector'),
          'colspan' => 1,
        ],
        'operations' => $this->t('Operations'),
      ],
      '#options' => $options,
      '#default_value' => array_combine($enabled_connectors, $enabled_connectors),
      '#empty' => $this->t('No localization server connectors found.'),
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $connectors = $form_state->getValue('connectors', []);
    $enabled = array_values(array_filter($connectors));
    $disabled = array_keys(array_diff($connectors, array_filter($connectors)));

    $this->connectorManager->removePluginConfigurationMultiple($disabled);
    $this->config('l10n_server.settings')
      ->set('enabled_connectors', $enabled)
      ->save();
  }

}
