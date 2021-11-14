<?php
declare(strict_types=1);

namespace Drupal\l10n_gettext\Plugin\l10n_server\Connector;

use Drupal\Component\Gettext\PoStreamReader;
use Drupal\file\FileInterface;
use Drupal\l10n_server\ConnectorPluginBase;
use Drupal\l10n_server\ConnnectorUploadHandlerInterface;
use Drupal\l10n_server\PoDatabaseWriter;

/**
 * A plugin to use pot files.
 *
 * @Connector(
 *   id = "gettext",
 *   label = @Translation("Gettext files"),
 *   description = @Translation("Drupal packages from the file system"),
 *   supported_sources = {
 *    "upload",
 *   }
 * )
 */
class GetText extends ConnectorPluginBase implements ConnnectorUploadHandlerInterface {

  public static function uploadHandler(FileInterface $file) {
    $reader = new PoStreamReader();
    $reader->setURI($file->getFileUri());
    $reader->open();
    $writer = new PoDatabaseWriter();
    $writer->writeItems($reader, -1);
    $file->delete();
  }

  public function getUploadValidators(): array {
    return ['file_validate_extensions' => ['pot']];
  }

}
