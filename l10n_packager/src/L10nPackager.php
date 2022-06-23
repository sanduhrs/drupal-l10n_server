<?php
declare(strict_types=1);

namespace Drupal\l10n_packager;

use Drupal\Core\Config\ConfigManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Language\Language;
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\l10n_packager\Entity\L10nPackagerFile;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;
use Drupal\l10n_server\L10nHelper;

/**
 * Service description.
 */
class L10nPackager {

  /**
   * Release packager status: do not repackage anymore.
   */
  const DISABLED = 0;

  /**
   * Release packager status: keep repackaging.
   */
  const ACTIVE = 1;

  /**
   * Release packager status: error.
   */
  const ERROR = 2;

  /**
   * Default path structure for generated files
   */
  const FILEPATH = '%core/%project/%project-%release.%language.po';

  /**
   * Packager API version.
   */
  const API_VERSION = '1.1';

  /**
   * Tha database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * The config manager.
   *
   * @var \Drupal\Core\Config\ConfigManagerInterface
   */
  protected ConfigManagerInterface $configManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected FileSystemInterface $fileSystem;

  /**
   * Constructs a L10nPackager object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Config\ConfigManagerInterface $config_manager
   *   The config manager.
   */
  public function __construct(
      Connection $database,
      ConfigManagerInterface $config_manager,
      FileSystemInterface $file_system
  ) {
    $this->database = $database;
    $this->configManager = $config_manager;
    $this->fileSystem = $file_system;
  }

  /**
   * Get files for a release, indexed by language.
   *
   * @param $rid
   *   A L10nServerRelease id.
   *
   * @return array
   *   An associative array, or an empty array if there is no result set.
   */
  public function getFiles($rid): array {
    $query = $this->database
      ->select('l10n_packager_file', 'r');
    $query
      ->leftJoin('file_managed', 'f', 'r.fid = f.fid');
    $query->condition('r.rid', $rid);
    return $query->execute()
      ->fetchAllAssoc('language');
  }

  /**
   * Get timestamp of the last updated string for a release, for each language.
   *
   * @param $rid
   *   A L10nServerRelease id.
   *
   * @return mixed
   */
  public function translationLastUpdated($rid) {
    $query = $this->database
      ->select('l10n_server_translation', 't');
    $query
      ->innerJoin('l10n_server_line', 'l', 't.sid = l.sid');
    $query
      ->addExpression('MAX(t.time_changed)', 'latest_time');
    $query
      ->fields('t', ['language', '']);
    $query
      ->condition('t.is_active', 1)
      ->condition('t.is_suggestion', 0)
      ->condition('l.rid', $rid);
    $query
      ->groupBy('t.language');
    return $query->execute()
      ->fetchAllKeyed();
  }

  /**
   * Get release object with packager data and some project data.
   */
  public function getRelease($rid) {
    if (is_object($rid)) {
      return $rid;
    }
    else {
      $query = $this->database
        ->select('l10n_server_project', 'p');
      $query
        ->innerJoin('l10n_server_release', 'r');
      $query
        ->leftJoin('l10n_packager_release');
      $query->fields('r', ['rid', 'pid', 'title']);
      $query->fields('pr', ['checked', 'updated', 'status']);
      $query->fields('p', ['uri']);
      $query->condition('rid', $rid);

      $release = $query->execute()->fetchObject();
      L10nHelper::releaseSetBranch($release);
      return $release;
    }
  }

  /**
   * Get release name.
   */
  public function releaseName($rid) {
    if ($release = $this->getRelease($rid)) {
      return $release->uri . '-' . $release->title;
    }
    else {
      return '';
    }
  }

  /**
   * Build target filepath from release object based on the set pattern.
   */
  public function getFilepath($release, $language = NULL, $pattern = NULL) {
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
      $pattern = $this->configManager->getConfigFactory()
        ->get('l10n_packager.settings')
        ->get('filepath');
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
  public function createLatestSymlink($file, $release, $language): bool {
    // If there is a minor version number, remove it. “Branch” is only
    // '{major}.x' or '{compatibility}-{major}.x'. So a new dev branch can fall
    // back to the latest translation for the major branch. For example, 9.1.x,
    // when there are no tagged 9.1.* releases, can get the 9.0.0-beta1
    // translations.
    $abbreviated_release = clone $release;
    $abbreviated_release->branch = preg_replace('/\.[0-9]+\.x$/', '.x', $abbreviated_release->branch);

    $target = $this->fileSystem->realpath($file);
    $latest_file = dirname($target) . '/' . $this->getFilepath($abbreviated_release, $language, '%project-%branch.%language.po');

    if (file_exists($latest_file)) {
      unlink($latest_file);
      $latest_file_object = new \stdClass();
      $latest_file_object->uri = l10n_packager_directory() . '/' . $this->getFilepath($abbreviated_release, $language, '%core/%project/%project-%branch.%language.po');
      // Allow modules to react to the symlink, such as purge a CDN.
      \Drupal::moduleHandler()->invokeAll('l10n_packager_done', [$latest_file_object]);
    }

    return symlink(basename($target), $latest_file);
  }

  /**
   * Generate a new file for a given release or update an existing one.
   *
   * @param $release
   *   Release object with uri and rid properties.
   * @param $language
   *   Language object.
   * @param L10nPackagerFileInterface|null $packager_file
   *   Release file object to be updated.
   * @param int|null $timestamp
   *   Timestamp to mark the files, for it to be consistent across tables.
   *
   * @return \Drupal\file\Entity\File|false
   *   Drupal file object or FALSE on error.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function releasePackage($release, Language $language, L10nPackagerFileInterface $packager_file = NULL, int $timestamp = NULL): bool|File {
    /** @var \Drupal\l10n_packager\L10nExporter $packager */
    $exporter = \Drupal::service('l10n_packager.exporter');

    $timestamp = $timestamp ?: \Drupal::time()->getRequestTime();
    $variables = [
      '%release' => $this->releaseName($release),
      '%language' => $language->getName(),
    ];

    if (!$packager_file) {
      $packager_file = L10nPackagerFile::create([
        'rid' => $release->rid,
        'language' => $language,
      ]);
    }

    // Generate PO file. Always export in compact form.
    $export_result = $exporter->export($release->uri, $release->rid, $language, FALSE, TRUE);

    if (!empty($export_result) && is_array($export_result)) {

      // If we got an array back from the export build, tear that into pieces.
      [$mime_type, $export_name, $serve_name, $sid_count] = $export_result;

      // Get the destination file path.
      $file_path = $this->getFilepath($release, $language);
      // Build the full path and make sure the directory exits.
      $file_path = $this->createPath($file_path);

      // Now build the Drupal file object or update the old one.
      if ($fid = $packager_file->get('fid')->first()->getValue()['target_id']) {
        $file = File::load($fid);
        $this->fileSystem->delete($file->getFileUri());
      }

      // Check / update / create all file data.
      $file->status = FileInterface::STATUS_PERMANENT;
      $file->timestamp = $file->checked = $timestamp;
      $file->filename = basename($file_path);
      $file->filemime = $mime_type;
      $file->uri = $file_path;
      $this->fileSystem->move($export_name, $file->uri, FileSystemInterface::EXISTS_REPLACE);
      $file->filesize = filesize($this->fileSystem->realpath($file->uri));
      $file->sid_count = $sid_count;
      // Create actual symlink to latest
      $this->createLatestSymlink($file_path, $release, $language);

      // Create / update file record and link to release.

      drupal_write_record('file_managed', $file, !empty($file->fid) ? 'fid' : []);
      drupal_write_record('l10n_packager_file', $file, !empty($file->drid) ? 'drid' : []);
      \Drupal::moduleHandler()->invokeAll('l10n_packager_done', [$file]);
      return $file;

    }
    else {
      \Drupal::logger('l10n_packager')
        ->error('Failed packaging release %release for language %language.', $variables);
      return FALSE;
    }
  }

  /**
   * Check release translations and repackage if needed.
   *
   * For each release we store packaging data in {l10n_packager_release}
   * - 'checked' is the last time all languages for this release were checked.
   * - 'updated' is the last time a file was updated for this release.
   *
   * We don't update the 'checked' field until we've checked all the languages
   * for this release, so we can keep track of releases and files and package a
   * few languages on every cron.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerReleaseInterface $release
   *   Release object.
   * @param bool $force
   *   Force repackage even when strings not updated.
   * @param int $limit
   *   Maximum number of files to create.
   * @param \Drupal\Core\Language\Language|null $language
   *   Optional language object to check only this one.
   * @param bool $cron
   *   In a cron run, a release may be packaged partially, for some languages.
   *
   * @return array
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function check(L10nServerReleaseInterface $release, bool $force = FALSE, int $limit = 0, Language $language = NULL, bool $cron = FALSE) {
    $check_languages = isset($language) ? [$language->language => $language] : \Drupal::languageManager()->getLanguages();
    $updated = [];
    // We get update time before creating files so the release checked time
    // is <= file timestamp.
    $timestamp = \Drupal::time()->getRequestTime();

    $files = $this->getFiles($release->id());
    $last_updated = $this->translationLastUpdated($release->id());

    // Get only the languages we have translations for, that need updating
    $languages = [];
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

    // Repackage this release for the remaining language list.
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

    if (isset($release->status)) {
      // Just update status of existing record.
      drupal_write_record('l10n_packager_release', $release, 'rid');
    }
    else {
      // The first time we checked this release, we need to add a new record.
      $release->status = L10N_PACKAGER_ACTIVE;
      drupal_write_record('l10n_packager_release', $release);
    }
    return $updated;
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
  public function package($release, $language, $file = NULL, $timestamp = NULL) {

    $timestamp = $timestamp ? $timestamp : REQUEST_TIME;
    $variables = array(
      '%release' => l10n_packager_release_name($release),
      '%language' => $language->name,
    );

    if (!$file) {
      $file = new stdClass();
      $file->rid = $release->rid;
      $file->language = $language->language;
    }

    // Generate PO file. Always export in compact form.
    $export_result = l10n_community_export($release->uri, $release->rid, $language, FALSE, TRUE);

    if (!empty($export_result) && is_array($export_result)) {

      // If we got an array back from the export build, tear that into pieces.
      [$mime_type, $export_name, $serve_name, $sid_count] = $export_result;

      // Get the destination file path.
      $file_path = l10n_packager_get_filepath($release, $language);
      // Build the full path and make sure the directory exits.
      $file_path = l10n_packager_create_path($file_path);

      // Now build the Drupal file object or update the old one.
      if (!empty($file->fid) && !empty($file->uri)) {
        file_unmanaged_delete($file->uri);
      }

      // Check / upate / create all file data.
      $file->status = FILE_STATUS_PERMANENT;
      $file->timestamp = $file->checked = $timestamp;
      $file->filename = basename($file_path);
      $file->filemime = $mime_type;
      $file->uri = $file_path;
      file_unmanaged_move($export_name, $file->uri, FILE_EXISTS_REPLACE);
      $file->filesize = filesize(drupal_realpath($file->uri));
      $file->sid_count = $sid_count;
      // Create actual symlink to latest
      l10n_packager_create_latest_symlink($file_path, $release, $language);

      // Create / update file record and link to release.
      drupal_write_record('file_managed', $file, !empty($file->fid) ? 'fid' : array());
      drupal_write_record('l10n_packager_file', $file, !empty($file->drid) ? 'drid' : array());
      module_invoke_all('l10n_packager_done', $file);
      return $file;

    }
    else {
      watchdog('l10n_packager', 'Failed packaging release %release for language %language.', $variables);
      return FALSE;
    }
  }

  /**
   * Ensure that directories on the $path exist in our packager directory.
   */
  function createPath($path) {
    $directory = dirname($path);
    $basepath = $currentpath = l10n_packager_directory();
    $finalpath = $basepath . '/' . $directory;
    $parts = explode('/', $directory);
    $htaccess_path = drupal_realpath($basepath) . '/.htaccess';
    if (!is_dir($basepath)) {
      file_prepare_directory($basepath, FILE_CREATE_DIRECTORY);
    }
    if (!file_exists($htaccess_path)) {
      $htaccess_lines = "\n\n<FilesMatch \"\.(po)$\">\n\tForceType \"text/plain; charset=utf-8\"\n\tAllow from ALL\n</FilesMatch>\n";
      file_create_htaccess($basepath, FALSE);
      drupal_chmod($htaccess_path, 0744);
      file_put_contents($htaccess_path, $htaccess_lines, FILE_APPEND);
      drupal_chmod($htaccess_path, 0444);
    }
    while (is_dir($currentpath) && !is_dir($finalpath) && ($more = array_shift($parts))) {
      $currentpath .= '/' . $more;
      file_prepare_directory($currentpath, FILE_CREATE_DIRECTORY);
    }
    return $basepath . '/' . $path;
  }

}
