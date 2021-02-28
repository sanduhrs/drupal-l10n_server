<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\l10n_server\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\l10n_server\ConfigurableSourcePluginBase;

/**
 * @Source(
 *   id = "filesystem",
 *   label = @Translation("The file system"),
 *   description = @Translation("Allows to use a file system path to find translations.")
 * )
 */
class FileSystem extends ConfigurableSourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['source_directory'] = [
      '#title' => $this->t('Source directory'),
      '#description' => $this->t('The directory on the local file system to be scanned for source data. Either relative to the Drupal site root or an absolute path on your file system. Drupal should have read access to the files and directories found there. Projects are identified based on subdirectory names or the first part of filenames if put directly in the root directory. Releases are identified based on the second part of package filenames. Examples: Fishbowl/fishbowl-1.2.tar.gz is from project "Fishbowl" in version 1.2, while campwizard-3.4.tar.gz is project "campwizard" in version 3.4.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $this->configuration['source_directory'],
    ];
    dpm($form);
    return $form;


//      '#title' => t('Source directory'),
//      '#description' => t('The directory on the local file system to be scanned for source data. Either relative to the Drupal site root or an absolute path on your file system. Drupal should have read access to the files and directories found there. Projects are identified based on subdirectory names or the first part of filenames if put directly in the root directory. Releases are identified based on the second part of package filenames. Examples: Fishbowl/fishbowl-1.2.tar.gz is from project "Fishbowl" in version 1.2, while campwizard-3.4.tar.gz is project "campwizard" in version 3.4.'),
//      '#type' => 'textfield',
//      '#required' => TRUE,
//      '#default_value' => variable_get('l10n_server_connector_' . $connector_name . '_' . $source_name . '_directory', conf_path() . '/files/' . $connector_name),
//      // Create directory by default if possible.
//      '#after_build' => array('l10n_server_connectors_files_check_directory'),
  //  );
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $configuration = parent::defaultConfiguration();
    $configuration['source_directory'] = '';
    return $configuration;
  }

}
