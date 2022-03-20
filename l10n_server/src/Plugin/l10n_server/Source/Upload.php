<?php

declare(strict_types=1);

namespace Drupal\l10n_server\Plugin\l10n_server\Source;

use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileInterface;
use Drupal\l10n_server\ConnnectorUploadHandlerInterface;
use Drupal\l10n_server\SourcePluginBase;
use Drupal\l10n_server\SourceString;

/**
 * @Source(
 *   id = "upload",
 *   label = @Translation("file upload"),
 * )
 */
final class Upload extends SourcePluginBase {

  public function uploadFormElement(&$form, FormStateInterface $form_state, ConnnectorUploadHandlerInterface $connector) {
    $form['new_source'] = [
      '#type' => 'file',
      '#title' => t('Source file'),
      '#description' => array(
        '#theme' => 'file_upload_help',
        '#description' => $this->t('Upload a source file to parse and store translatable strings from for this release.'),
        '#upload_validators' =>  $connector->getUploadValidators(),
      ),
      '#upload_validators' => $connector->getUploadValidators(),
    ];
    $form['#validate'][] = [$this, 'validateUpload'];
    $form['actions']['submit']['#submit'][] = [$this, 'uploadHandler'];
    $form_state->setTemporaryValue('connector', $connector);
  }

  public static function validateUpload($form, FormStateInterface $form_state) {
    /** @var \Drupal\l10n_server\ConnnectorUploadHandlerInterface $connector */
    $connector = $form_state->getTemporaryValue('connector');
    $files = file_save_upload('new_source', $connector->getUploadValidators());
    $file = $files ? \reset($files) : NULL;
    if (!$file instanceof FileInterface) {
      $form_state->setErrorByName('new_source');
    }
    else {
      $form_state->setValue('new_source', $file);
    }
  }

  public static function uploadHandler(&$form, FormStateInterface $form_state) {
    if ($form_state->getValue('new_source') instanceof FileInterface) {
      SourceString::$counter = 0;
      /** @var \Drupal\l10n_server\ConnnectorUploadHandlerInterface $connector */
      $connector = $form_state->getTemporaryValue('connector');
      $connector::uploadHandler($form_state->getValue('new_source'));
      /** @var \Drupal\l10n_server\Entity\ReleaseInterface $release */
      $release = $form_state->getFormObject()->getEntity();
      $release->setLastParsed();
      $release->setSourceStringCounter(SourceString::$counter);
      $release->save();
      \Drupal::messenger()->addStatus(
        \Drupal::translation()->formatPlural(
          SourceString::$counter,
          'The source file was successfully imported. One source string was added.',
          'The source file was successfully imported. @count source strings were added.'),
      );
    }
  }

}
