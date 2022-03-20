<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\l10n_server\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\l10n_server\ConfigurableSourcePluginBase;

/**
 * @Source(
 *   id = "filesystem",
 *   label = @Translation("file system"),
 * )
 */
final class FileSystem extends ConfigurableSourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['source_directory'] =  PublicStream::basePath() . DIRECTORY_SEPARATOR . 'l10n_filesytem';
    $config['scan_limit'] =  1;
    $config['cron_enabled'] =  FALSE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['source_directory'] = [
      '#title' => $this->t('Source directory'),
      '#description' => $this->t('The directory on the local file system to be scanned for source data. Either relative to the Drupal site root or an absolute path on your file system. Drupal should have read access to the files and directories found there. Projects are identified based on subdirectory names or the first part of filenames if put directly in the root directory. Releases are identified based on the second part of package filenames. Examples: Fishbowl/fishbowl-1.2.tar.gz is from project "Fishbowl" in version 1.2, while campwizard-3.4.tar.gz is project "campwizard" in version 3.4.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->getSourceDirectory(),
      '#after_build' => ['system_check_directory'],
    ];
    $form['scan_limit'] = [
      '#title' => $this->t('Number of releases to look at once'),
      '#description' => $this->t('The number of releases to scan on a manual or cron run. Scanning is synchronous, so you need to wait while extraction and parsing of file content is underway.'),
      '#type' => 'number',
      '#default_value' => $this->getScanLimit(),
      '#min' => 1,
    ];
    $form['cron_enabled'] = array(
      '#title' => t('Run scanning on cron'),
      '#type' => 'checkbox',
      '#default_value' => $this->isCronEnabled(),
      '#description' => $this->t('It is advised to set up a regular cron run to parse new files, instead of hitting the Scan tab manually.'),
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function supportScan(): bool {
    return TRUE;
  }

  public function getSourceDirectory(): string {
    return $this->configuration['source_directory'];
  }

  public function getScanLimit(): int {
    return (int) $this->configuration['scan_limit'];
  }

  public function isCronEnabled(): bool {
    return (bool) $this->configuration['cron_enabled'];
  }


}
