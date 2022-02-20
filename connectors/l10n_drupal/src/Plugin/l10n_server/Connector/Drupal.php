<?php
declare(strict_types=1);

namespace Drupal\l10n_drupal\Plugin\l10n_server\Connector;

use Drupal\Core\Utility\ProjectInfo;
use Drupal\l10n_drupal\Exception\MissingProjectCodeNameOrVersionNumberException;
use Drupal\l10n_server\ConnectorPluginBase;
use Drupal\l10n_server\ConnnectorScanHandlerInterface;
use Drupal\l10n_server\Entity\Project;
use Drupal\l10n_server\Entity\ProjectInterface;
use Drupal\l10n_server\Entity\Release;
use Drupal\l10n_server\Entity\ReleaseInterface;
use function assert;
use function basename;
use function explode;
use function filemtime;
use function reset;
use function sprintf;

/**
 * A plugin to use source code of drupal package.
 *
 * @Connector(
 *   id = "drupal",
 *   label = @Translation("Drupal packages"),
 *   description = @Translation("Drupal packages from the file system"),
 *   supported_sources = {
 *    "filesystem"
 *   }
 * )
 */
class Drupal extends ConnectorPluginBase implements ConnnectorScanHandlerInterface {

  private const MAJOR_VERSION = [5, 6, 7, 8, 9];


  public function fileExtension(): string {
    return 'tar.gz';
  }

  public function scanHandler(array $files, string $sourceDirectory): void {
    $stop = TRUE;

    foreach ($files as $path => $file) {
      if (!$this->isSupportedVersion($path)) {
        // Skip files for unsupported versions.
        continue;
      }
      // Get rid of $workdir prefix on file names, eg.
      // files/Drupal/drupal-4.6.7.tar.gz or
      // files/Ubercart/ubercart-5.x-1.0-alpha8.tar.gz.
      $path = $package = trim(preg_replace('!(^' . preg_quote($sourceDirectory, '!') . ')(.+)\.tar\.gz!', '\2', $path), '/');

      $project_title = NULL;
      if (strpos($path, '/')) {
        // We have a slash, so this package is in a subfolder.
        // Eg. Drupal/drupal-4.6.7 or Ubercart/ubercart-5.x-1.0-alpha8.
        // Grab the directory name as project title.
        [$project_title, $package] = explode('/', $path);
      }
      if (strpos($package, '-')) {
        // Only remaining are the project uri and release,
        // eg. drupal-4.6.7 or ubercart-5.x-1.0-alpha8.
        [$project_uri, $release_version] = explode('-', $package, 2);
      }
      else {
        throw new MissingProjectCodeNameOrVersionNumberException();
      }
      $projectIds = \Drupal::entityTypeManager()
        ->getStorage('l10n_server_project')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('uri', $project_uri)->range(0, 1)->execute();
      if ($projectIds !== [] && ($projectId = reset($projectIds)) && $project = Project::load($projectId)) {
        assert($project instanceof ProjectInterface);
        if ($project->getConnectorModule() === $this->pluginId) {
          if (!$project->getEnabled()) {
            continue;
          }
          if ($project_title) {
            $project
              ->set('title', $project_title)
              ->set('last_parsed', NULL)
              ->save();
          }
        }
        else {
          throw new \Exception(sprintf('An existing project under the URI %s is already handled by %s. Not possible to add it with %s.', $project_uri, $project->getConnectorModule(), $this->pluginId));
        }

      }
      else {
        // @todo set homepage
        $project = Project::create([
          'title' => $project_title ?? $project_uri,
          'uri' => $project_uri,
          'connector_module' => $this->pluginId,
        ])->save();
      }
    }
    $releaseIds = \Drupal::entityTypeManager()
      ->getStorage('l10n_server_release')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('pid', $project->id())
      ->condition('title', $release_version)
      ->range(0, 1)->execute();

    if ($releaseIds !== [] && ($releaseId = reset($releaseIds)) && $release = Release::load($releaseId)) {
      assert($release instanceof ReleaseInterface);
      $release
        ->set('last_parsed', NULL)
        ->set('file_date', filemtime($file->uri))
        ->set('download_link', $path . '.' . $this->fileExtension())
        ->save();
    }
    else {
      Release::create([
        'pid' => $project->id(),
        'title' => $release_version,
        'file_date' => filemtime($file->uri),
        'download_link' => $path . '.' . $this->fileExtension(),
      ])->save();
    }

  }

  private function isSupportedVersion(string $path): bool {
    return in_array($this->detectMajorVersion($path), self::MAJOR_VERSION);
  }

  private function detectMajorVersion(string $path): int {
    // Only interested in the filename.
    $filename = basename($path);
    // The project name could not contain hyphens, as the project name equals
    // function name prefixes, and hyphens are not allowed in function names.
    [, $version] = explode('-', $filename);
    // The major number is the first digit (eg. 6 for 6.x-dev, 4 for 4.7.x).
    return (int) $version;
  }

}
