<?php

declare(strict_types=1);

namespace Drupal\l10n_drupal_rest\Plugin\l10n_server\Connector;

use Drupal\l10n_server\Annotation\Connector;
use Drupal\l10n_server\ConnectorPluginBase;
use Drush\Drush;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A plugin to use source code of drupal.org package.
 *
 * @Connector(
 *   id = "drupal_rest",
 *   label = @Translation("Drupal.org packages"),
 *   deriver = "Drupal\l10n_server\Plugin\Derivative\ConnectorSources",
 *   supported_sources = {
 *    "restapi"
 *   }
 * )
 */
class DrupalRest extends ConnectorPluginBase {

  /**
   * @var \Drupal\Core\File\FileSystem
   */
  private $fileSystem;

  /**
   * @var \GuzzleHttp\Client
   */
  private $httpClient;

  /**
   * @var \Drupal\Core\Database\Connection
   */
  private $databaseConnection;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->fileSystem = $container->get('file_system');
    $instance->httpClient = $container->get('http_client');
    $instance->databaseConnection = $container->get('database');
    return $instance;
  }

  // @todo: type $release (query result object).
  public function drupalOrgParseRelease($release) {
    $filename = basename($release->download_link);
    $package_file = $this->fileSystem->getTempDirectory() . '/' . $filename;

    \Drupal::logger('l10n_drupal_rest')->notice('Retrieving @filename for parsing.', ['@filename' => $filename]);

    // Check filename for a limited set of allowed chars.
    if (!preg_match('!^([a-zA-Z0-9_.-])+$!', $filename)) {
      \Drupal::logger('l10n_drupal_rest')->error('Filename %file contains malicious characters.', ['%file' => $package_file]);
      return FALSE;
    }

    // Already downloaded. Probably result of faulty file download left around,
    // so remove file.
    if (file_exists($package_file)) {
      unlink($package_file);
      \Drupal::logger('l10n_drupal_rest')->warning('File %file already exists, deleting.', ['%file' => $package_file]);
    }

    // Download the tar.gz file from Drupal.org and save it.
    if (!(($contents = $this->httpClient->get($release->download_link)) && ($contents->code == 200) && file_put_contents($package_file, $contents->data))) {
      \Drupal::logger('l10n_drupal_rest')->error('Unable to download and save %download_link file (%error).', [
        '%download_link' => $release->download_link,
        '%error' => $contents->code . ' ' . $contents->error,
      ]);
      return FALSE;
    }

    // Set up status messages if not in automated mode.
    //@todo: Check this call is still operational.
    potx_status('set', POTX_STATUS_MESSAGE);

    // Generate temp folder to extract the tarball.
    $temp_path = drush_tempdir();

    // Nothing to do if the file is not there.
    if (!file_exists($package_file)) {
      \Drupal::logger('l10n_drupal_rest')->error('Package to parse (%file) does not exist.', ['%file' => $package_file]);
      return FALSE;
    }

    // Extract the local file to the temporary directory.
    if (!Drush::process('tar -xvvzf %s -C %s', $package_file, $temp_path)) {
      \Drupal::logger('l10n_drupal_rest')->error('Failed to extract %file.', ['%file' => $package_file]);
      return FALSE;
    }

    \Drupal::logger('l10n_drupal_rest')->notice('Parsing extracted @filename for strings.', ['@filename' => $filename]);

    // Get all source files and save strings with our callback for this release.
    $release->uri = explode('-', $filename)[0];
    //@todo: Check this call is still operational.
    l10n_packager_release_set_branch($release);
    if ($release->core === 'all') {
      $version = POTX_API_8;
    }
    else {
      $version = explode('.', $release->core)[0];
    }
    _l10n_drupal_potx_init();
    $files = _potx_explore_dir($temp_path, '*', $version);
    l10n_drupal_save_file([$release->pid, $release->rid]);
    l10n_drupal_added_string_counter(NULL, TRUE);
    foreach ($files as $name) {
      _potx_process_file($name, strlen($temp_path) + 1, 'l10n_drupal_save_string', 'l10n_drupal_save_file', $version);
    }
    potx_finish_processing('l10n_drupal_save_string', $version);

    $sid_count = l10n_drupal_added_string_counter();

    // Delete directory now that parsing is done.
    Drush::process('rm -rf %s', $temp_path);
    unlink($package_file);

    // Record changes of the scanned project in the database.
    \Drupal::logger('l10n_drupal_rest')->notice('@filename (@files files, @sids strings) scanned.', [
      '@filename' => $filename,
      '@files' => count($files),
      '@sids' => $sid_count,
    ]);

    // Parsed this releases files.
    $this->databaseConnection->update('l10n_server_release')
      ->fields([
        'sid_count' => $sid_count,
        'last_parsed' => REQUEST_TIME,
      ])
      ->condition('rid', $release->rid)
      ->execute();

    // Update error list for this release. Although the errors are related to
    // files, we are not interested in the fine details, the file names are in
    // the error messages as text. We assume no other messages are added while
    // importing, so we can safely use drupal_get_message() to grab our errors.
    $this->databaseConnection->delete('l10n_server_error')->condition('rid', $release->rid)->execute();
    $messages = $this->messenger->messagesByType('error');
    if (isset($messages['error']) && is_array($messages['error'])) {
      foreach ($messages['error'] as $error_message) {
        $this->databaseConnection->insert('l10n_server_error')
          ->fields([
            'rid' => $release->rid,
            'value' => $error_message,
          ])
          ->execute();
      }
    }

    // Clear stats cache, so new data shows up.
    //@todo: Implement a better caching strategy (tags).
    //cache_clear_all('l10n:stats', 'cache');

    return TRUE;
  }

}
