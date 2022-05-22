<?php

declare(strict_types=1);

namespace Drupal\l10n_packager;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\l10n_server\Entity\Release;
use Drupal\l10n_server\Entity\ReleaseInterface;
use stdClass;

/**
 * Packager manager class.
 */
class PackagerManager {

  /**
   * Check releases that need repackaging.
   */
  public function checkUpdates(int $interval = 1, int $release_limit = 10, int $file_limit = 1): array {
    $count_check = $count_files = $time = 0;

    if ($interval) {
      $time_pre = microtime(true);

      $timestamp = \Drupal::time()->getRequestTime() - $interval;

      $query = \Drupal::database()
        ->select('l10n_server_release', 'r');
      $query
        ->innerJoin('l10n_server_project', 'p', 'r.pid = p.pid');
      $query
        ->leftJoin('l10n_packager_release', 'pr', 'pr.rid = r.rid');
      $query
        ->fields('r', ['rid', 'pid','title']);
      $query
        ->fields('p', ['uri']);
      $query
        ->fields('pr', ['checked', 'updated','status']);
      $orGroup2 = $query->orConditionGroup()
        ->condition('pr.checked', $timestamp, '<')
        ->condition('pr.updated', $timestamp, '<');
      $andGroup = $query->andConditionGroup()
        ->condition('pr.status', 1)
        ->condition($orGroup2);
      $orGroup1 = $query->orConditionGroup()
        ->condition('pr.status', NULL, 'IS NULL')
        ->condition($andGroup);
      $query->condition($orGroup1);
      $query->orderBy('pr.checked');
      if ($release_limit) {
        $query->range(0, $release_limit);
      }
      $result = $query->execute();

      while ((!$file_limit || $file_limit > $count_files)
          && ($release = $result->fetchObject())) {
        // Set the release branch
        module_load_include('inc', 'l10n_packager');
        $this->releaseSetBranch($release);
        $updates = $this->releaseCheck($release, FALSE, $file_limit - $count_files, NULL, TRUE);
        $count_files += count($updates);
        $count_check++;
      }
      $time_post = microtime(true);
      $time = $time_post - $time_pre;

      \Drupal::logger('l10n_packager')
        ->notice('@ms ms for @checked releases/@repack files.', [
          '@checked' => $count_check,
          '@repack' => $count_files,
          '@ms' => $time,
        ]);
    }
    return [$count_check, $count_files, $time];
  }

  /**
   * Check release translations and repackage if needed.
   *
   * For each release we store packaging data in {l10n_packager_release}
   * - 'checked' is the last time all languages for this release were checked.
   * - 'updated' is the last time a file was updated for this release.
   *
   * We don't update the 'checked' field until we've checked all the languages for
   * this release, so we can keep track of releases and files and package a few
   * languages on every cron.
   *
   * @param $release
   *   Release object.
   * @param $force
   *   Force repackage even when strings not updated.
   * @param $limit
   *   Maximum number of files to create.
   * @param $language
   *   Optional language object to check only this one.
   * @param $cron
   *   In a cron run, a release may be packaged partially, for some languages.
   */
  public function releaseCheck($release, $force = FALSE, $limit = 0, $language = NULL, $cron = FALSE): array {
    $check_languages = isset($language) ? array($language->language => $language) : \Drupal::languageManager()->getLanguages();
    $updated = [];
    // We get update time before creating files so the release checked time
    // is <= file timestamp.
    $timestamp = \Drupal::time()->getRequestTime();

    $files = $this->releaseGetFiles($release->rid);
    $last_updated = $this->translationLastUpdated($release->rid);

    // Get only the languages we have translations for, that need updating
    $languages = array();
    foreach ($check_languages as $langcode => $language) {
      if (!empty($last_updated[$langcode]) && ($force || empty($files[$langcode]) || ($last_updated[$langcode] > $files[$langcode]->checked))) {
        $languages[$langcode] = $language;
      }
    }

    // For this special case we check we didn't stop before in the middle of a
    // release. Otherwise it could stick on a release forever when forcing.
    if ($force && $cron && $release->checked < $release->updated) {
      foreach ($files as $lang => $file) {
        if (!empty($file->checked) && ($file->checked > $release->checked)) {
          unset($languages[$lang]);
        }
      }
    }

    // Repackage this relase for the remaining language list.
    while ((!$limit || $limit > count($updated)) && ($language = array_shift($languages))) {
      $langcode = $language->language;
      // Warning: this may upload release data with or without file.
      $existing = !empty($files[$langcode]) ? $files[$langcode] : NULL;
      $updated[$langcode] = $this->releasePackage($release, $language, $existing, $timestamp);
    }

    // Update the release data.
    if (!count($languages)) {
      // We only mark the release checked if there are no languages left.
      $release->checked = $timestamp;
    }
    if ($updated) {
      $release->updated = $timestamp;
    }

    if (!isset($release->status)) {
      $release->status = L10N_PACKAGER_ACTIVE;
    }
    $upsert = \Drupal::database()
      ->upsert('l10n_packager_release')
      ->fields(['rid', 'status', 'checked', 'updated'])
      ->key('rid');
    $upsert->values((array) $release)
      ->execute();
    return $updated;
  }

  /**
   * Ensure that directories on the $path exist in our packager directory.
   */
  public function createPath($path) {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $config = \Drupal::config('l10n_packager.settings');

    $directory = dirname($path);
    $basepath = $currentpath = $config->get('directory');

    $finalpath = $basepath . '/' . $directory;
    $parts = explode('/', $directory);
    $htaccess_path = $file_system->realpath($basepath) . '/.htaccess';
    if (!is_dir($basepath)) {
      $file_system->prepareDirectory($basepath, FileSystemInterface::CREATE_DIRECTORY);
    }

    if (!file_exists($htaccess_path)) {
      $htaccess_lines = "\n\n<FilesMatch \"\.(po)$\">\n\tForceType \"text/plain; charset=utf-8\"\n\tAllow from ALL\n</FilesMatch>\n";
      static::writeFile($basepath, '.htaccess', $htaccess_lines, TRUE);
    }

    while (is_dir($currentpath) && !is_dir($finalpath) && ($more = array_shift($parts))) {
      $currentpath .= '/' . $more;
      $file_system->prepareDirectory($currentpath, FileSystemInterface::CREATE_DIRECTORY);
    }
    return $basepath . '/' . $path;
  }

  /**
   * Generate a new file for a given release or update an existing one.
   *
   * @param $release
   *   Release object with uri and rid properties.
   * @param $language
   *   Language object.
   * @param $file
   *   Release file object to be updated.
   * @param $timestamp
   *   Timestamp to mark the files, for it to be consistent across tables.
   *
   * @return
   *   Drupal file object or FALSE on error.
   */
  public function releasePackage(ReleaseInterface $release, LanguageInterface $language, FileInterface $file = NULL, $timestamp = NULL) {
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    if (!$file) {
      $file = File::create();
    }

    $timestamp = $timestamp ? $timestamp : \Drupal::time()->getRequestTime();
    $variables = [
      '%release' => $this->releaseName($release->label()),
      '%language' => $language->getName(),
    ];

    // Generate PO file. Always export in compact form.
    $export_result = l10n_community_export($release->uri, $release->rid, $language, FALSE, TRUE);

    if (!empty($export_result) && is_array($export_result)) {

      // If we got an array back from the export build, tear that into pieces.
      [$mime_type, $export_name, $serve_name, $sid_count] = $export_result;

      // Get the destination file path.
      $file_path = $this->getFilepath($release, $language);
      // Build the full path and make sure the directory exits.
      $file_path = $this->createPath($file_path);

      // Now build the Drupal file object or update the old one.
      if (!empty($file->fid) && !empty($file->uri)) {
        $file_system->delete($file->uri);
      }

      // Update file record.
      $file->set('status', FileInterface::STATUS_PERMANENT);
      $file->set('changed', $timestamp);
      $file->set('filename', basename($file_path));
      $file->set('filemime', $mime_type);
      $file->set('uri', $file_path);

      $file_system->move($export_name, $file->uri, FileSystemInterface::EXISTS_REPLACE);
      $file->set('filesize', filesize($file_system->realpath($file->get('uri'))));
      $file->save();

      // Create actual symlink to latest
      $this->createLatestSymlink($file_path, $release, $language);

      $drid = \Drupal::database()
        ->select('l10n_packager_file', 'f')
        ->fields('f', ['drid'])
        ->condition('rid', $release->id())
        ->condition('fid', $file->id())
        ->execute()->fetchField();
      // Link file record to release.
      \Drupal::database()
        ->upsert('l10n_packager_file')
        ->fields(['drid', 'rid', 'language', 'fid', 'checked', 'sid_count'])
        ->values([$drid, $release->id(), $language->getId(), $file->id(), $timestamp, $sid_count])
        ->key('drid')
        ->execute();

      \Drupal::moduleHandler()->invokeAll('l10n_packager_done', [$file]);
      return $file;

    }
    else {
      \Drupal::logger('l10n_packager')->error('Failed packaging release %release for language %language.', $variables);
      return FALSE;
    }
  }

  /**
   * Build target filepath from release object based on the set pattern.
   */
  function getFilepath($release, $language = NULL, $pattern = NULL) {
    $replace = [
      '%project' => $release->uri,
      '%release' => $release->title,
      '%core' => $release->core,
      '%version' => $release->version,
      '%branch' => $release->branch,
      '%extra' => !empty($extra) ? '-' . $extra : '',
      '%language' => isset($language) ? $language->language : '',
    ];
    if (!isset($pattern)) {
      $pattern = \Drupal::config('l10n_packager.settings')->get('filepath');
    }
    return strtr($pattern, $replace);
  }

  /**
   * Create a symlink to the latest version of this locale for the project.
   *
   * The symlink name has the pattern [project]-[branch].[langcode].po and will
   * be placed is the same directory as the translation file.
   *
   * @param $file
   *   The translation file to be symlinked.
   * @param $release
   *   Object containing the file's release information.
   * @param $language
   *   Language object.
   *
   * @return bool
   *   Returns TRUE if a symlink was created.
   */
  function createLatestSymlink($file, $release, $language): bool {
    $config = \Drupal::config('l10n_packager');

    // If there is a minor version number, remove it. “Branch” is only
    // '{major}.x' or '{compatibility}-{major}.x'. So a new dev branch can fall
    // back to the latest translation for the major branch. For example, 9.1.x,
    // when there are no tagged 9.1.* releases, can get the 9.0.0-beta1
    // translations.
    $abbreviated_release = clone $release;
    $abbreviated_release->branch = preg_replace('/\.[0-9]+\.x$/', '.x', $abbreviated_release->branch);

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $target = $file_system->realpath($file);
    $latest_file = dirname($target) . '/' . $this->getFilepath($abbreviated_release, $language, '%project-%branch.%language.po');

    if (file_exists($latest_file)) {
      unlink($latest_file);
      $latest_file_object = new stdClass();
      $latest_file_object->uri = $config->get('directory') . '/' . $this->getFilepath($abbreviated_release, $language, '%core/%project/%project-%branch.%language.po');
      // Allow modules to react to the symlink, such as purge a CDN.
      \Drupal::moduleHandler()->invokeAll('l10n_packager_done', [$latest_file_object]);
    }

    return symlink(basename($target), $latest_file);
  }

  /**
   * Get release object with packager data and some project data.
   */
  function getRelease($rid): object {
    $query = \Drupal::database()
      ->select('l10n_server_project', 'p');
    $query
      ->innerJoin('l10n_server_release', 'r', 'p.pid = r.pid');
    $query
      ->leftJoin('l10n_packager_release', 'pr', 'r.rid = pr.rid');
    $query->condition('rid', $rid);
    $release = $query->execute()->fetchObject();
    $this->releaseSetBranch($release);
    return $release;
  }

  /**
   * Get release name.
   */
  function releaseName($rid): string {
    if ($release = $this->getRelease($rid)) {
      return $release->uri . '-' . $release->title;
    }
    else {
      return '';
    }
  }

  /**
   * Get timestamp of the last updated string for a release, for each language.
   */
  function translationLastUpdated($rid): array {
    $query = \Drupal::database()
      ->select('l10n_server_translation', 't');
    $query
      ->innerJoin('l10n_server_line', 'l', 't.sid = l.sid');
    $query
      ->condition('is_active', 1)
      ->condition('is_suggestion', 0)
      ->condition('rid', $rid)
      ->groupBy('language');
    $query
      ->fields('t', ['language'])
      ->addExpression('MAX("time_changed")', 't.latest_time');
    $result = $query->execute();
    return $result->fetchAllKeyed();
  }

  /**
   * Get files for a release, indexed by language.
   */
  public function releaseGetFiles($rid): array {
    $query = \Drupal::database()
      ->select('l10n_packager_file', 'r');
    $query
      ->leftJoin('file_managed', 'f', 'r.fid = f.fid');
    $query
      ->condition('r.rid', $rid);
    $query
      ->fields('f', 'fid');
    $result = $query->execute()
      ->fetchAllAssoc('fid');
    return File::loadMultiple($result);
  }

  /**
   * Determine the branch for a release. We should get this into a cross-site
   * packaging module.
   */
  public function releaseSetBranch(&$release): void {
    // Set branch to everything before the last dot, and append an x. For
    // example, 6.1, 6.2, 6.x-dev, 6.0-beta1 all become 6.x. 8.7.13 becomes
    // 8.7.x. 6.x-1.0-beta1 becomes 6.x-1.x. 2.1.0-rc1 becomes 2.1.x.
    $release->branch = preg_replace('#\.[^.]*$#', '.x', $release->title);

    // Stupid hack for drupal core.
    if ($release->uri === 'drupal') {
      // Version has -extra removed, 6.0-beta1 becomes 6.0.
      $release->version = explode('-', $release->title)[0];
      // Major version is the first component before the .
      $major = explode('.', $release->branch)[0];
      if ($major >= 8) {
        // In D8 & later, start removing “API compatibility” part of the path.
        $release->core = 'all';
      }
      else {
        $release->core = $major . '.x';
      }
    }
    else {
      // Modules are like: 6.x-1.0, 6.x-1.x-dev, 6.x-1.0-beta1, 2.0.0, 5.x-dev,
      // 2.1.x-dev, 2.1.0-rc1. If there is a core API compatibility component,
      // split it off. version here is the main version number, without the
      // -{extra} component, like -beta1 or -rc1.
      preg_match('#^(?:(?<core>(?:4\.0|4\.1|4\.2|4\.3|4\.4|4\.5|4\.6|4\.7|5|6|7|8|9)\.x)-)?(?<version>[0-9.x]*)(?:-.*)?$#', $release->title, $match);
      $release->core = $match['core'] ?: 'all';
      $release->version = $match['version'];
    }
  }

  /**
   * Writes the contents to the file in the given directory.
   *
   * @param string $directory
   *   The directory to write to.
   * @param string $filename
   *   The file name.
   * @param string $contents
   *   The file contents.
   * @param bool $force
   *   TRUE if we should force the write over an existing file.
   *
   * @return bool
   *   TRUE if writing the file was successful.
   */
  protected static function writeFile($directory, $filename, $contents, $force) {
    $file_path = $directory . DIRECTORY_SEPARATOR . $filename;
    // Don't overwrite if the file exists unless forced.
    if (file_exists($file_path) && !$force) {
      return TRUE;
    }
    // Writing the file can fail if:
    // - concurrent requests are both trying to write at the same time.
    // - $directory does not exist or is not writable.
    // Testing for these conditions introduces windows for concurrency issues to
    // occur.
    if (@file_put_contents($file_path, $contents)) {
      return @chmod($file_path, 0444);
    }
    return FALSE;
  }

  /**
   * Export meta information in a simple XML format for remote use.
   */
  public function exportMetafile() {
    $config = \Drupal::config('l10n_packager');

    // Start off with a root element of l10nserver.
    $xml = new SimpleXMLElement("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<l10n_server></l10n_server>");

    // Add version of this XML format.
    $xml->addChild('version', L10N_PACKAGER_API_VERSION);

    // Add server meta information.
    $xml->addChild('name', \Drupal::config('site.settings')->get('name'));
    $server_url = Url::fromUri('internal:/')->setAbsolute()->toString();
    $xml->addChild('link', $server_url);
    if ($url = $config->get('update_url')) {
      $xml->addChild('update_url', $url . '/' . $config->get('filepath'));
    }

    // We also inform the client whether this server accepts remote string
    // submissions so the client can auto-configure itself.
    if (\Drupal::moduleHandler()->moduleExists('l10n_remote')) {
      $xml->addChild('l10n_remote', $server_url);
    }

    // Add language list.
    $languages = $xml->addChild('languages');
    $language_list = \Drupal::languageManager()->getLanguages();
    foreach ($language_list as $language) {
      $item = $languages->addChild('language');
      $item->addChild('name', $language->getName());
      $item->addChild('code', $language->getId());
      // @todo Fix native value.
      // $item->addChild('native', $language->getNative());
    }

    // Export to static file.
    $xml_path = \Drupal::config('l10n_packager.settings')->get('directory');
    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');
    $file_system->prepareDirectory($xml_path, FileSystemInterface::CREATE_DIRECTORY);
    if ($xml->asXML($xml_path . '/l10n_server.xml')) {
      \Drupal::messenger()->addMessage(t('Server information XML exported to %file.', [
        '%file' => $xml_path . '/l10n_server.xml',
      ]));
    }
    else {
      \Drupal::messenger()->addError(t('Error when trying to export server info XML to %file.', [
        '%file' => $xml_path . '/l10n_server.xml',
      ]));
    }
  }

  /**
   * Generate a download URL for a PO file.
   *
   * @param object $project
   * @param string $branch
   * @param object $file
   * @return string
   */
  function getDownloadUrl($project, $branch, $file) {
    $download_url = \Drupal::config('l10n_packager.settings')->get('update_url');
    if ($download_url) {
      module_load_include('inc', 'l10n_packager');
      $release = new stdClass();
      $release->title = $file->title;
      $release->uri = $project->uri;
      $this->releaseSetBranch($release);
      return $download_url . '/' . $release->core . '/' . $project->uri . '/' . $file->filename;
    }
    return file_create_url($file->uri);
  }

}
