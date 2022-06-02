<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\l10n_server\ConnectorInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class ConnectorBatchConfirmScanForm extends ConnectorBatchConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'l10n_server_connector_batch_confirm_scan';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to start scanning?');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\l10n_server\ConnectorInterface $connector */
    $connector = $form_state->getTemporaryValue('connector');
    $batch = [
      'title' => t('Scanning'),
      'operations' => [],
      'finished' => static::class . '::batchFinished',
    ];
    $source_config = $connector->getSourceInstance()->getConfiguration();
    for ($i = 0; $i < $source_config['scan_limit']; $i++) {
      $batch['operations'][] = [
        static::class . '::batchOperation',
        [
          $connector,
        ],
      ];
    }
    batch_set($batch);
    $form_state->setRedirectUrl(new Url('l10n_server.connectors'));
  }

  /**
   * Batch operation callback.
   *
   * @param \Drupal\l10n_server\ConnectorInterface $connector
   *   A connector.
   * @param array $context
   *   The batch context.
   */
  public static function batchOperation(ConnectorInterface $connector, array &$context): void {
    if (!$connector->isScannable()) {
      return;
    }

    /** @var \Drupal\l10n_server\ConnectorScanHandlerResult $result */
    $result = $connector->scanHandler();

    for ($i = 0; $i < $result->getProjectCount(); $i++) {
      $context['results'][] = Html::escape(t('A project hase been created.'));
    }
    for ($i = 0; $i < $result->getReleaseCount(); $i++) {
      $context['results'][] = Html::escape(t('A release hase been created.'));
    }
  }

}
