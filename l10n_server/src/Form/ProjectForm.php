<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityConstraintViolationListInterface;
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $connectors = $this->connector_manager->getOptionsList();
    if (\count($connectors) === 0) {
      // @todo Event -> redirect connectors page.
      $this->messenger()->addError($this->t('You need to enable at least one localization server connector.'));
      return $form;
    }

    return parent::buildForm($form, $form_state);
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
        'label' => $this->t('Project URI'),
      ],
      // A project's machine name cannot be changed.
      '#disabled' => !$project->isNew(),
    ];

    $connectors = $this->connector_manager->getOptionsList();
    $initial_value = \count($connectors) === 1 ? \key($connectors) : NULL;
    $form['connector_module'] = [
      '#type' => 'radios',
      '#title' => $this->t('Connector handling project data'),
      '#description' => $this->t('Data and source handler for this project. Cannot be modified later.'),
      '#default_value' => !$project->isNew() ? $project->getConnectorModule() : $initial_value,
      '#options' => $connectors,
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
  public function projectUriExists(string $value): bool {
    // Check first to see if a project with this ID exists.
    if ($this->entityTypeManager->getStorage('l10n_server_project')->getQuery()->accessCheck(FALSE)->condition('uri', $value)->range(0, 1)->count()->execute()) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditedFieldNames(FormStateInterface $form_state) {
    return \array_merge(['connector_module', 'uri'], parent::getEditedFieldNames($form_state));
  }

  /**
   * {@inheritdoc}
   */
  protected function flagViolations(EntityConstraintViolationListInterface $violations, array $form, FormStateInterface $form_state) {
    foreach ($violations->getByField('connector_module') as $violation) {
      $form_state->setErrorByName('connector_module', $violation->getMessage());
    }
    foreach ($violations->getByField('uri') as $violation) {
      $form_state->setErrorByName('uri', $violation->getMessage());
    }
    parent::flagViolations($violations, $form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $saved = parent::save($form, $form_state);
    switch ($saved) {
      case SAVED_NEW:
        $this->messenger()->addStatus($this->t('Created new project %project.', ['%project' => $this->entity->label()]));
        $this->logger('l10n_server')->notice('Created new project %project.', ['%project' => $this->entity->label()]);
        break;

      case SAVED_UPDATED:
        $this->messenger()->addStatus($this->t('Updated project %project.', ['%project' => $this->entity->label()]));
        $this->logger('l10n_server')->notice('Updated project %project.', ['%project' => $this->entity->label()]);
        break;
    }
    $form_state->setRedirectUrl($this->entity->toUrl('collection'));
    return $saved;
  }

}
