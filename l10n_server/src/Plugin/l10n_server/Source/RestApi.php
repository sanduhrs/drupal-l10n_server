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
 *   description = @Translation("Retrieve from drupal.org"),
 * )
 */
final class RestApi extends ConfigurableSourcePluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    $config = parent::defaultConfiguration();
    $config['max_filesize'] = 50 * 1024 * 1024;
    $config['refresh_url'] = 'https://www.drupal.org/files/releases.tsv';
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['max_filesize'] = [
      '#title' => $this->t('Release files max filesize'),
      '#description' => $this->t('In bytes. Releases larger than this size will not be downloaded.'),
      '#type' => 'number',
      '#default_value' => $this->configuration['max_filesize'],
      '#min' => 1,
    ];
    $form['refresh_url'] = [
      '#title' => $this->t('Refresh URL'),
      '#description' => $this->t('URL to the releases.tsv file.'),
      '#type' => 'textfield',
      '#default_value' => $this->configuration['refresh_url'],
    ];
    return $form;
  }

}
