<?php
declare(strict_types=1);

namespace Drupal\l10n_gettext\Plugin\l10n_server\Connector;

use Drupal\file\FileInterface;
use Drupal\l10n_server\ConnectorPluginBase;
use Drupal\l10n_server\ConnnectorUploadHandlerInterface;

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

  public function uploadHandler(FileInterface $file) {
    dpm(func_get_args(), __METHOD__);
  }

  public function getUploadValidators(): array {
    return ['file_validate_extensions' => ['pot']];
  }

}
