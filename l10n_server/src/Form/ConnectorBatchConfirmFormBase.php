<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\l10n_server\ConnectorInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
abstract class ConnectorBatchConfirmFormBase extends ConfirmFormBase {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl(): Url {
    return new Url('l10n_server.connectors');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, ConnectorInterface $connector = NULL): array {
    $form_state->setTemporaryValue('connector', $connector);
    return parent::buildForm($form, $form_state);
  }

  /**
   * Batch finished callback.
   *
   * @param bool $success
   *   The success status boolean.
   * @param array $results
   *   The results array.
   * @param array $operations
   *   The remaining operations.
   * @param string $elapsed
   *   The time elapsed.
   */
  public static function batchFinished(bool $success, array $results, array $operations, string $elapsed): void {
    if ($success) {
      // Here we do something meaningful with the results.
      $message = t("@count items were processed (@elapsed).", [
        '@count' => count($results),
        '@elapsed' => $elapsed,
      ]);
      \Drupal::messenger()->addStatus($message);
    }
    else {
      // An error occurred.
      // $operations contains the operations that remained unprocessed.
      $error_operation = reset($operations);
      $message = t('An error occurred while processing %error_operation with arguments: @arguments', [
        '%error_operation' => $error_operation[0],
        '@arguments' => print_r($error_operation[1], TRUE),
      ]);
      \Drupal::messenger()->addError($message);
    }
  }

}
