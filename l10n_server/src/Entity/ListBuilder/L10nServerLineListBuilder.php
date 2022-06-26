<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the line entity type.
 */
class L10nServerLineListBuilder extends EntityListBuilder {

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
    $build['summary']['#markup'] = $this->t('Total lines: @total', [
      '@total' => $total,
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['pid'] = $this->t('Project ID');
    $header['rid'] = $this->t('Release ID');
    $header['fid'] = $this->t('File ID');
    $header['lineno'] = $this->t('Line no.');
    $header['sid'] = $this->t('String ID');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\l10n_server\Entity\L10nServerLineInterface $entity */
    $row['id'] = $entity->id();
    $row['pid'] = $entity->getProjectId();
    $row['rid'] = $entity->getReleaseId();
    $row['fid'] = $entity->getFileId();
    $row['lineno'] = $entity->getLineNumber();
    $row['sid'] = $entity->getStringId();
    $row['type'] = $entity->getType();
    return $row + parent::buildRow($entity);
  }

}
