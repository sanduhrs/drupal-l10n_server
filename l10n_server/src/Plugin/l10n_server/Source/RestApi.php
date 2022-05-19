<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\l10n_server\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_server\ConfigurableSourcePluginBase;

/**
 * Source plugin to retrieve releases from drupal.org.
 *
 * @Source(
 *   id = "restapi",
 *   label = @Translation("Rest API"),
 *   description = @Translation("Retrieve from drupal.org")
 * )
 */
final class RestApi extends ConfigurableSourcePluginBase {

  const REFRESH_URL = 'https://www.drupal.org/files/releases.tsv';

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['scan_limit'] = 1;
    $config['cron_enabled'] = FALSE;
    $config['max_filesize'] = 50 * 1024 * 1024;
    $config['refresh_url'] = RestApi::REFRESH_URL;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['scan_limit'] = [
      '#title' => $this->t('Number of releases to look at once'),
      '#description' => $this->t('The number of releases to scan on a manual or cron run. Scanning is synchronous, so you need to wait while extraction and parsing of file content is underway.'),
      '#type' => 'number',
      '#default_value' => $this->getScanLimit(),
      '#min' => 1,
    ];

    $form['cron_enabled'] = [
      '#title' => t('Run scanning on cron'),
      '#type' => 'checkbox',
      '#default_value' => $this->isCronEnabled(),
      '#description' => $this->t('It is advised to set up a regular cron run to parse new files, instead of hitting the Scan tab manually.'),
    ];

    $form['max_filesize'] = [
      '#title' => $this->t('Release files max filesize'),
      '#description' => $this->t('In bytes. Releases larger than this size will not be downloaded.'),
      '#type' => 'number',
      '#default_value' => $this->getMaxFileSize(),
      '#min' => 1,
    ];

    $form['refresh_url'] = [
      '#title' => $this->t('Refresh URL'),
      '#description' => $this->t('URL to the releases.tsv file.'),
      '#type' => 'textfield',
      '#default_value' => $this->getRefreshUrl(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function supportScan(): bool {
    return FALSE;
  }

  /**
   * Get scan limit, as defined in configuration.
   *
   * @return int
   *   Scan limit
   */
  public function getScanLimit(): int {
    return (int) $this->configuration['scan_limit'];
  }

  /**
   * Check whether cron is enabled, as defined in configuration.
   *
   * @return bool
   *   Whether this source should be executed during cron.
   */
  public function isCronEnabled(): bool {
    return (bool) $this->configuration['cron_enabled'];
  }

  /**
   * Get max file size, as defined in configuration.
   *
   * @return int
   *   Max file size, in bytes.
   */
  public function getMaxFileSize(): int {
    return (int) $this->configuration['max_filesize'];
  }

  /**
   * Get refresh URL, as defined in configuration.
   *
   * @return string
   *   Refresh URL.
   */
  public function getRefreshUrl(): string {
    return $this->configuration['refresh_url'];
  }

}
