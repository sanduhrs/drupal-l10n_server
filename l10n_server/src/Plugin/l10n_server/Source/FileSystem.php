<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\l10n_server\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\l10n_server\ConfigurableSourcePluginBase;

/**
 * Filesystem source plugin.
 *
 * @Source(
 *   id = "filesystem",
 *   label = @Translation("file system"),
 *   description = @Translation("Retrieve from file system"),
 * )
 */
final class FileSystem extends ConfigurableSourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['source_directory'] = PublicStream::basePath() . DIRECTORY_SEPARATOR . 'l10n_filesytem';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['source_directory'] = [
      '#title' => $this->t('Source directory'),
      '#description' => $this->t('The directory on the local file system to be scanned for source data. Either relative to the Drupal site root or an absolute path on your file system. Drupal should have read access to the files and directories found there. Projects are identified based on subdirectory names or the first part of filenames if put directly in the root directory. Releases are identified based on the second part of package filenames. Examples: Fishbowl/fishbowl-1.2.tar.gz is from project "Fishbowl" in version 1.2, while campwizard-3.4.tar.gz is project "campwizard" in version 3.4.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configuration['source_directory'],
      '#after_build' => ['system_check_directory'],
    ];
    return $form;
  }

}
