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
    $header['pid'] = $this->t('PID');
    $header['rid'] = $this->t('RID');
    $header['fid'] = $this->t('FID');
    $header['lineno'] = $this->t('Line no.');
    $header['sid'] = $this->t('SID');
    $header['type'] = $this->t('Type');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\l10n_server\Entity\L10nServerLineInterface $entity */
    $row['id'] = $entity->id();
    $row['pid'] = $entity->get('pid')->first()->getValue()['target_id'];
    $row['rid'] = $entity->get('rid')->first()->getValue()['target_id'];
    $row['fid'] = $entity->get('fid')->first()->getValue()['target_id'];
    $row['lineno'] = $entity->get('lineno')->first()->getValue()['value'];
    $row['sid'] = $entity->get('sid')->first()->getValue()['target_id'];
    $row['type'] = $entity->get('type')->first()->getValue()['value'];
    return $row + parent::buildRow($entity);
  }

}
