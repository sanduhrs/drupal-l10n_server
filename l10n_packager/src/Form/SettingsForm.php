<?php
declare(strict_types=1);

namespace Drupal\l10n_packager\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
<<<<<<< Updated upstream
use Drupal\Core\Url;
use Drupal\l10n_server\ConfigurableConnectorInterface;
use Drupal\l10n_server\ConfigurableSourceInterface;
use Drupal\l10n_server\ConnectorInterface;
use function array_diff;
use function assert, array_values, array_combine, array_filter;
=======
>>>>>>> Stashed changes

/**
 * Defines a form that configures l10n_packager settings.
 */
final class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'l10n_packager_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames(): array {
    return [
      'l10n_packager.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $settings = $this->config('l10n_server.settings');


//    $header = [
//      'connector' => ['data' => $this->t('Connector'), 'colspan' => 1],
//      'operations' => $this->t('Operations'),
//    ];

//    $settings = $this->config('l10n_server.settings');
//    $enabled_connectors = $settings->get('enabled_connectors') ?? [];
//
//    $connectors = $this->connectorManager->getDefinitions();
//    $options = [];
//    foreach ($connectors as $id => $definition) {
//      $connector = $this->connectorManager->createInstance($id);
//      assert($connector instanceof ConnectorInterface);
//      $options[$connector->getPluginId()] = [
//        'connector' => [$this->t('@title from @source', array('@title' => $connector->getLabel(), '@source' => $connector->getSourceInstance()->getLabel()))],
//      ];
//      if (!$connector->isEnabled()) {
//        continue;
//      }
//      $links = NULL;
//      $options[$connector->getPluginId()]['operations'] = ['data' => ['#type' => 'operations', '#links' => $links]];
//      $source = $connector->getSourceInstance();
//      if ($source instanceof ConfigurableSourceInterface || $connector instanceof ConfigurableConnectorInterface) {
//        $links['configure'] = [
//          'title' => $this->t('Configure'),
//          'url' => Url::fromRoute('l10n_server.connector.configure', ['connector' => $connector->getPluginId()]),
//        ];
//      }
//      if ($source->supportScan()) {
//        $links['scan'] = [
//          'title' => $this->t('Scan'),
//          'url' => Url::fromRoute('l10n_server.connector.scan', ['connector' => $connector->getPluginId()]),
//        ];
//      }
//      if ($links) {
//        $options[$connector->getPluginId()]['operations']['data']['#links'] = $links;
//      }
//      else {
//        $options[$connector->getPluginId()]['operations'] = '';
//      }
//    }
//    $form['connectors'] = array(
//      '#type' => 'tableselect',
//      '#header' => $header,
//      '#options' => $options,
//      '#default_value' => array_combine($enabled_connectors, $enabled_connectors),
//      '#empty' => $this->t('No localization server connectors found.'),
//    );

    $cron = $settings->get('l10n_packager_cron') ?? 0;
    $form['l10n_packager_cron'] = array(
      '#title' => t('Generate packages on every Drupal cron run'),
      '#type' => 'checkbox',
      '#default_value' => $cron,
    );

    //@todo: port l10n_packager_directory().
    $directory = $settings->get('l10n_packager_directory') ?? null /*l10n_packager_directory()*/;
    $form['l10n_packager_directory'] = array(
      '#title' => t('Directory for generated packages'),
      '#description' => t('The directory on the local file system to use to store packages generated. Either relative to the Drupal installation directory or an absolute path on your file system. Drupal should have read and write access to the files and directories found there.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $directory,
      // Create directory by default if possible.
      //@todo: port l10n_packager_admin_check_directory().
      /*'#after_build' => array('l10n_packager_admin_check_directory'),*/
    );

    //@todo: port l10n_packager_directory().
    $update_url = $settings->get('l10n_packager_update_url') ?? null /*file_create_url(l10n_packager_directory())*/;
    $form['l10n_packager_update_url'] = array(
      '#title' => t('Root URL for translation downloads'),
      '#type' => 'textfield',
      '#default_value' => $update_url,
      '#description' => t('Root URL for the client to build file URLs and fetch updates. The public facing URL for the package directory defined above. Leave blank for not providing any.'),
    );

    $file_path = $settings->get('l10n_packager_filepath') ?? L10N_PACKAGER_FILEPATH;
    $form['l10n_packager_filepath'] = array(
      '#title' => t('Path structure for generated packages'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => $file_path,
      '#description' => t("Available tokens are: %project, %release, %core, %version, %extra, %language."),
    );

    $options = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 30, 40, 50, 60, 70, 80, 90, 100];
    $release_limit = $settings->get('l10n_packager_release_limit') ?? 10;
    $form['l10n_packager_release_limit'] = array(
      '#title' => t('Number of releases to check at once'),
      '#description' => t('The number of releases to check on a manual or cron run.'),
      '#type' => 'select',
      '#options' => array_combine($options, $options),
      '#default_value' => $release_limit,
    );

    $options = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 30, 40, 50, 60, 70, 80, 90, 100];
    $file_limit = $settings->get('l10n_packager_file_limit') ?? 1;
    $form['l10n_packager_file_limit'] = array(
      '#title' => t('Maximum number of files to package at once'),
      '#description' => t('The number of files to package on a manual or cron run.'),
      '#type' => 'select',
      '#options' => array_combine($options, $options),
      '#default_value' => $file_limit,
    );

    // Logging settings
    $update = $settings->get('l10n_packager_file_limit') ?? 0;
    $options = [3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600, 2419200, 4838400, 9676800];
    $callback = function ($value) {
      return \Drupal::service('date.formatter')->formatInterval($value);
    };
    $period = array(0 => t('Never'), 1 => t('Every cron run')) + array_map($callback, array_combine($options, $options));
    $form['l10n_packager_update'] = array(
      '#title' => t('Repackaging interval'),
      '#type' => 'select',
      '#options' => $period,
      '#default_value' => $update,
      '#description' => t('Time interval for the translations to be automatically repackaged.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $origin = $form_state->getValue('connectors', []);
    $enabled = array_values(array_filter($origin));
    $disabled = array_keys(array_diff($origin, array_filter($origin)));
    $this->connectorManager->removePluginConfigurationMultiple($disabled);
    $this->config('l10n_packager.settings')
      ->set('enabled_connectors', $enabled)
      ->save();
  }

}
