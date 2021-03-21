<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the default form handler for the Project entity.
 */
class ProjectForm extends ContentEntityForm {

  /**
   * @var \Drupal\l10n_server\ConnectorManager
   */
  protected $connector_manager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->connector_manager = $container->get('plugin.manager.l10n_server.connector');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\l10n_server\Entity\ProjectInterface $project */
    $project = $this->entity;
    $form = parent::form($form, $form_state);
    $form['uri'] = [
      '#type' => 'machine_name',
      '#title' => $this->t('Project URI'),
      '#default_value' => $project->get('uri')->value,
      '#maxlength' => 50,
      '#description' => $this->t('A unique name to construct the URL for the project. It must only contain lowercase letters, numbers and hyphens.'),
      '#required' => TRUE,
      '#machine_name' => [
        'exists' => [$this, 'projectUriExists'],
        'source' => ['title', 'widget', 0, 'value'],
        'replace_pattern' => '[^a-z0-9-]+',
        'replace' => '-',
        'standalone' => TRUE,
        'label' => $this->t('Project URI')
      ],
      // A project's machine name cannot be changed.
      '#disabled' => !$project->isNew(),
    ];
    $form['connector_module'] = [
      '#type' => 'radios',
      '#title' => $this->t('Connector handling project data'),
      '#description' => $this->t('Data and source handler for this project. Cannot be modified later.'),
      '#default_value' => $project->getConnectorModule(),
      '#options' => $this->connector_manager->getOptionsList(),
      '#required' => TRUE,
      '#disabled' => !$project->isNew(),
    ];
    return $form;
  }

  /**
   * Returns whether a project uri already exists.
   *
   * @param string $value
   *   The uri of the project.
   *
   * @return bool
   *   Returns TRUE if the project uri already exists, FALSE otherwise.
   */
  public function projectUriExists($value) {
    // Check first to see if a project with this ID exists.
    if ($this->entityTypeManager->getStorage('l10n_server_project')->getQuery()->condition('pid', $value)->range(0, 1)->count()->execute()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $saved;
  }

}
