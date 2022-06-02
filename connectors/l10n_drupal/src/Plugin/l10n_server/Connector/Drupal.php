<?php
declare(strict_types=1);

namespace Drupal\l10n_drupal\Plugin\l10n_server\Connector;

use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_server\ConfigurableConnectorPluginBase;
use Drupal\l10n_server\ConnectorParseHandlerInterface;
use Drupal\l10n_server\ConnectorParseHandlerResult;
use Drupal\l10n_server\ConnectorParseHandlerResultInterface;
use Drupal\l10n_server\ConnectorScanHandlerResult;
use Drupal\l10n_server\ConnectorScanHandlerResultInterface;
use Drupal\l10n_server\ConnectorScanHandlerInterface;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;

/**
 * A plugin to use source code of drupal package.
 *
 * @Connector(
 *   id = "drupal",
 *   label = @Translation("Drupal packages"),
 *   deriver = "Drupal\l10n_server\Plugin\Derivative\ConnectorSources",
 *   supported_sources = {
 *     "filesystem",
 *     "upload",
 *   }
 * )
 */
class Drupal extends ConfigurableConnectorPluginBase implements ConnectorScanHandlerInterface, ConnectorParseHandlerInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['drupal_home'] = TRUE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['drupal_home'] = [
      '#title' => $this->t('Assign drupal.org home links to packages'),
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['drupal_home'],
      '#description' => $this->t('Assigns https://drupal.org/project/<em>project</em> type home links to projects. These home links are used to guide users to the home pages of the projects. The setting only affects newly parsed packages.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function parseHandler(L10nServerReleaseInterface $release = NULL): ConnectorParseHandlerResultInterface {
    // @todo Fix parser handling.
    return new ConnectorParseHandlerResult([
      'files' => rand(0, 9),
      'lines' => rand(0, 99),
      'strings' => rand(0, 999),
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function scanHandler(): ConnectorScanHandlerResultInterface {
    // @todo Fix scanner handling.
    return new ConnectorScanHandlerResult([
      'projects' => rand(0, 9),
      'releases' => rand(0, 99),
    ]);
  }

}
