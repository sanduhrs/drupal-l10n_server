<?php
declare(strict_types=1);

namespace Drupal\l10n_server;

use Drupal\Component\Utility\Unicode;
use Drupal\l10n_server\Entity\L10nServerFile;
use Drupal\l10n_server\Entity\L10nServerLine;
use Drupal\l10n_server\Entity\L10nServerReleaseInterface;
use Drupal\l10n_server\Entity\L10nServerString;

/**
 * Service description.
 */
class L10nHelper {

  /**
   * Determine the branch for a release.
   *
   * @param \Drupal\l10n_server\Entity\L10nServerReleaseInterface $release
   *   The release object.
   */
  public static function releaseSetBranch(L10nServerReleaseInterface &$release): void {
    // Set branch to everything before the last dot, and append an x. For
    // example, 6.1, 6.2, 6.x-dev, 6.0-beta1 all become 6.x. 8.7.13 becomes
    // 8.7.x. 6.x-1.0-beta1 becomes 6.x-1.x. 2.1.0-rc1 becomes 2.1.x.
    $release->branch = preg_replace('#\.[^.]*$#', '.x', $release->label());

    // Stupid hack for drupal core.
    if ($release->uri === 'drupal') {
      // Version has -extra removed, 6.0-beta1 becomes 6.0.
      $release->version = explode('-', $release->label())[0];

      // Major version is the first component before the dot (.).
      $major = explode('.', $release->branch)[0];
      if ($major >= 8) {
        // In D8 & later, start removing 'API compatibility' part of the path.
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
      preg_match('#^(?:(?<core>(?:4\.0|4\.1|4\.2|4\.3|4\.4|4\.5|4\.6|4\.7|5|6|7|8|9)\.x)-)?(?<version>[0-9.x]*)(?:-.*)?$#', $release->label(), $match);
      $release->core = $match['core'] ?: 'all';
      $release->version = $match['version'];
    }
  }

  /**
   * CVS revision saver callback for potx.
   *
   * We call it with a release id if $file is not given. And we ask for a file
   * ID (to save the string with), if $revision is not given.
   *
   * This is called:
   *  - before any file parsing with (array($pid, $rid), NULL)
   *  - just as a new file is found by potx with ($revision, $file)
   *  - just as a new string is found by our own callback with (NULL, $file)
   *
   * @param string|array $revision
   *   CVS revision information about $file. If not given, the recorded fid of
   *   $file will be returned in an array with ($pid, $rid, $fid).
   * @param string $file
   *   File location in package. If not given, $revision is taken as an array
   *   with project and release id to use to store the file list.
   *
   * @return array|null
   *   An array of data for a specific file or NULL.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public static function saveFile($revision = NULL, $file = NULL) {
    static $pid = 0;
    static $rid = 0;
    static $files = [];

    if (!isset($file)) {
      // We get the release number for the files.
      [$pid, $rid] = $revision;
    }
    elseif (!isset($revision)) {
      // We return data for a specific file.
      return [$pid, $rid, $files[$file]];
    }
    else {
      $existing_file = \Drupal::database()
        ->select('l10n_server_file', 'l')
        ->fields('l')
        ->condition('rid', $rid)
        ->condition('location', $file)
        ->execute()
        ->fetchObject();
      if ($existing_file) {
        if ($existing_file->revision != $revision) {
          // Changed revision on a file.
          /** @var \Drupal\l10n_server\Entity\L10nServerFileInterface $entity */
          $entity = L10nServerFile::load($existing_file->fid);
          $entity->set('revision', $revision)->save();
        }
        $fid = $existing_file->fid;
      }
      else {
        if (!$pid || !$rid) {
          return;
        }
        // New file in this release.
        /** @var \Drupal\l10n_server\Entity\L10nServerFileInterface $entity */
        $entity = L10nServerFile::create([
          'pid' => $pid,
          'rid' => $rid,
          'location' => $file,
          'revision' => $revision,
        ]);
        $entity->save();
        $fid = $entity->id();
      }
      $files[$file] = $fid;
    }
  }

  /**
   * A counter we use for strings added. Each source strings is counted once.
   *
   * @param string $sid
   *   The string to count.
   * @param bool $reset
   *   Whether to reset the static cache.
   *
   * @return int|null
   *   A count integer or void.
   */
  public static function addedStringCounter($sid = NULL, $reset = FALSE) {
    static $sids = [];

    if ($reset) {
      $sids = [];
    }
    elseif (empty($sid)) {
      return count($sids);
    }
    else {
      $sids[$sid] = 1;
    }
  }

  /**
   * String saving callback for potx.
   *
   * @param string $value
   *   String value to store.
   * @param string $context
   *   From Drupal 7, separate contexts are supported. POTX_CONTEXT_NONE is
   *   the default, if the code does not specify a context otherwise.
   * @param string $file
   *   Name of file the string occurred in.
   * @param int $line
   *   Number of line the string was found.
   * @param int $string_type
   *   String type: POTX_STRING_INSTALLER, POTX_STRING_RUNTIME
   *   or POTX_STRING_BOTH.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @todo More elegant plural handling.
   * @todo Find a way to properly use POTX constants before potx.inc is loaded.
   */
  public static function saveString($value = NULL, $context = NULL, $file = NULL, $line = 0, $string_type = 2 /*POTX_STRING_RUNTIME*/): void {
    static $files = [];

    // Strip all slashes from string.
    $value = stripcslashes($value);

    if (!isset($files[$file])) {
      // Get file ID for saving, locally cache.
      $files[$file] = self::saveFile(NULL, $file);
    }

    // Value set but empty. Mark error on empty translatable string. Only trim
    // for empty string checking, since we should store leading/trailing
    // whitespace as it appears in the string otherwise.
    $check_empty = trim($value);
    if (empty($check_empty)) {
      potx_status('error', t('Empty string attempted to be localized. Please do not leave test code for localization in your source.'), $file, $line);
      return;
    }

    if (!Unicode::validateUtf8($value)) {
      potx_status('error', t('Invalid UTF-8 string attempted to be localized.'), $file, $line);
      return;
    }

    // If we have the file entry now, we can process adding the string.
    if (isset($files[$file])) {
      // Explode files array to pid, rid and fid.
      [$pid, $rid, $fid] = $files[$file];
      // Context cannot be null.
      $context = !is_null($context) ? $context : '';

      // A \0 separator in the string means we deal with a string with plural
      // variants. Unlike Drupal core, we store all in the same string, as it is
      // easier to handle later, and we don't need the individual string parts.
      $sid = \Drupal::database()
        ->select("l10n_server_string", 'l')
        ->fields('l', ['sid'])
        ->condition('hashkey', md5($value . $context))
        ->execute()
        ->fetchField();
      if (!$sid) {
        // String does not exist.
        $entity = L10nServerString::create([
          'value' => $value,
          'context' => $context,
          'hashkey' => md5($value . $context),
        ]);
        $entity->save();
        $sid = $entity->id();
      }
      $existing_fid = \Drupal::database()
        ->select('l10n_server_line', 'l')
        ->fields('l', ['fid'])
        ->condition('fid', $fid)
        ->condition('sid', $sid)
        ->condition('lineno', $line)
        ->condition('type', $string_type)
        ->execute()
        ->fetchField();
      if (!$existing_fid) {
        $entity = L10nServerLine::create([
          'pid' => $pid,
          'rid' => $rid,
          'fid' => $fid,
          'sid' => $sid,
          'lineno' => $line,
          'type' => $string_type,
        ]);
        $entity->save();
      }
      self::addedStringCounter($sid);
    }
  }

}
