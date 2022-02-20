<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\Exception\NotRegularDirectoryException;
use Drupal\l10n_drupal\Exception\MissingProjectCodeNameOrVersionNumberException;
use Drupal\l10n_server\ConnectorInterface;
use Drupal\l10n_server\ConnnectorScanHandlerInterface;
use Drupal\l10n_server\Exception\SourceDoesNotSupportScan;
use Drupal\l10n_server\Plugin\l10n_server\Source\FileSystem;
use Drupal\l10n_server\SourceInterface;
use function array_slice;
use function assert;
use function preg_quote;
use function sprintf;

class ConnectorController extends ControllerBase {

  public function scan(ConnectorInterface $connector, SourceInterface $source) {
    if (!$source->supportScan()) {
      throw new SourceDoesNotSupportScan();
    }
    assert($connector instanceof ConnnectorScanHandlerInterface);
    assert($source instanceof FileSystem);
    /** @var \Drupal\Core\File\FileSystemInterface $fileSystem */
    $fileSystem = \Drupal::service('file_system');
    try {
      $files = $fileSystem->scanDirectory($source->getSourceDirectory(), sprintf('/%s$/', preg_quote($connector->fileExtension(), '!')));
    }
    catch (NotRegularDirectoryException $exception) {
      $this->messenger()->addError($exception->getMessage());
    }
    if ($files !== []) {
      $files = array_slice($files, 0, $source->getScanLimit());

      try {
        $connector->scanHandler($files, $source->getSourceDirectory());
      }
      catch (MissingProjectCodeNameOrVersionNumberException $exception) {
        $this->messenger()->addError($exception->getMessage());
      }
    }
    else{
      $this->messenger()->addWarning(
        $this->t('Did not found any files (@pattern) in @directory',
          [
            '@pattern' => $connector->fileExtension(),
            '@directory' => $source->getSourceDirectory(),
          ]
        )
      );
    }
    return $this->redirect('l10n_server.connectors');
  }

}
