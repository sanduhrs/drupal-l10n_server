<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\ListBuilder;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the string entity type.
 */
class L10nServerStringListBuilder extends EntityListBuilder {

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
    $build['summary']['#markup'] = $this->t('Total strings: @total', [
      '@total' => $total,
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['value'] = $this->t('Value');
    $header['context'] = $this->t('Context');
    $header['hashkey'] = $this->t('Hashkey');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\l10n_server\Entity\L10nServerStringInterface $entity */
    $row['id'] = $entity->id();
    $row['value'] = $entity->getValue();
    $row['context'] = $entity->getContext();
    $row['hashkey'] = $entity->getHashkey();
    return $row + parent::buildRow($entity);
  }

}
