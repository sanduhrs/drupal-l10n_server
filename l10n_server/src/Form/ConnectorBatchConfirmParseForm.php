<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Component\Utility\Html;
use Drupal\Core\Database\Connection;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Drupal\l10n_server\ConnectorInterface;

/**
 * Provides a confirmation form before clearing out the examples.
 */
class ConnectorBatchConfirmParseForm extends ConnectorBatchConfirmFormBase {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create($container) {
    $form = new static();
    $form->setStringTranslation($container->get('string_translation'));
    $form->setLoggerFactory($container->get('logger.factory'));
    $form->setMessenger($container->get('messenger'));
    $form->setRedirectDestination($container->get('redirect.destination'));
    $form->setStringTranslation($container->get('string_translation'));
    $form->database = $container->get('database');
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'l10n_server_connector_batch_confirm_parse';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestion(): TranslatableMarkup {
    return $this->t('Are you sure you want to start parsing?');
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\l10n_server\ConnectorInterface $connector */
    $connector = $form_state->getTemporaryValue('connector');
    $batch = [
      'title' => t('Parsing'),
      'operations' => [],
      'finished' => static::class . '::batchFinished',
    ];

    $queued = $this->database
      ->select('l10n_server_release', 'r')
      ->condition('r.queued', 0, '>')
      ->countQuery()->execute()->fetchField();
    for ($i = 0; $i < $queued; $i++) {
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
    if (!$connector->isParsable()) {
      return;
    }

    /** @var \Drupal\l10n_server\ConnectorParseHandlerResult $result */
    $result = $connector->parseHandler();

    if ($result->getFileCount()) {
      $context['results'][] = Html::escape(t('A project release with @files files, @lines lines and @strings strings has been parsed.', [
        '@files' => $result->getFileCount(),
        '@lines' => $result->getLineCount(),
        '@strings' => $result->getStringCount(),
      ]));
    }
  }

}
