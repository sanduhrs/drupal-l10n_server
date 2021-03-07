<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\Handler;

use Drupal\Core\Entity\EntityListBuilder;
use Drupal\l10n_server\Entity\ProjectInterface;

/**
 * Provides the list builder handler for the Project entity.
 */
class ProjectListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [];
    $header['label'] = $this->t('Project');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow($entity) {
    assert($entity instanceof ProjectInterface);
    $row = [];
    $row['label']['data'] = [
      '#type' => 'item',
      '#title' => $entity->label(),
    ];
    return $row + parent::buildRow($entity);
  }

}
