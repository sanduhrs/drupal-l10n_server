<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginWithFormsTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function array_intersect_key;

/**
 * Configurable source plugin base class.
 */
abstract class ConfigurableSourcePluginBase extends SourcePluginBase implements ConfigurableSourceInterface {

  use PluginWithFormsTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setConfiguration($configuration);
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration): void {
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'scan_limit' => 1,
      'cron_scanning_enabled' => FALSE,
      'parse_limit' => 1,
      'cron_parsing_enabled' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state): array {
    $form['scan_limit'] = [
      '#title' => $this->t('Number of projects to scan at once'),
      '#description' => $this->t('The number of projects to scan for releases on a manual or cron run. Scanning is synchronous, so you need to wait while each project is scanned.'),
      '#type' => 'number',
      '#default_value' => $this->getScanLimit(),
      '#min' => 1,
    ];
    $form['cron_scanning_enabled'] = [
      '#title' => t('Run scanning on cron'),
      '#type' => 'checkbox',
      '#default_value' => $this->isCronScanningEnabled(),
      '#description' => $this->t('It is advised to set up a regular cron run to scan for new files, instead of hitting the Scan tab manually.'),
    ];
    $form['parse_limit'] = [
      '#title' => $this->t('Number of releases to parse once'),
      '#description' => $this->t('The number of a releases to parse on a manual or cron run. Parsing is synchronous, so you need to wait while extraction and parsing of file content is underway.'),
      '#type' => 'number',
      '#default_value' => $this->getParseLimit(),
      '#min' => 1,
    ];
    $form['cron_parsing_enabled'] = [
      '#title' => t('Run parsing on cron'),
      '#type' => 'checkbox',
      '#default_value' => $this->isCronParsingEnabled(),
      '#description' => $this->t('It is advised to set up a regular cron run to parse new files, instead of hitting the Parse tab manually.'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state): void {
    // Validation is optional.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state): void {
    $form_state->cleanValues();
    foreach (array_intersect_key($form_state->getValues(), $this->configuration) as $config_key => $config_value) {
      $this->configuration[$config_key] = $config_value;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getScanLimit(): int {
    return (int) $this->configuration['scan_limit'];
  }

  /**
   * {@inheritdoc}
   */
  public function isCronScanningEnabled(): bool {
    return (bool) $this->configuration['cron_scanning_enabled'];
  }

  /**
   * {@inheritdoc}
   */
  public function getParseLimit(): int {
    return (int) $this->configuration['parse_limit'];
  }

  /**
   * {@inheritdoc}
   */
  public function isCronParsingEnabled(): bool {
    return (bool) $this->configuration['cron_parsing_enabled'];
  }

}
