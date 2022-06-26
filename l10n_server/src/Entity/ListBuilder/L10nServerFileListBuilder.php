<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the file entity type.
 */
class L10nServerFileListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build['table'] = parent::render();
    $total = $this->getStorage()
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $build['summary']['#markup'] = $this->t('Total files: @total', [
      '@total' => $total,
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['pid'] = $this->t('Prpject ID');
    $header['rid'] = $this->t('Release ID');
    $header['location'] = $this->t('Location');
    $header['revision'] = $this->t('Revision');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\l10n_server\Entity\L10nServerFileInterface $entity */
    $row['id'] = $entity->id();
    $row['pid'] = $entity->getProjectId();
    $row['rid'] = $entity->getReleaseId();
    $row['location'] = $entity->getLocation();
    $row['revision'] = $entity->getRevision();
    return $row + parent::buildRow($entity);
  }

}
