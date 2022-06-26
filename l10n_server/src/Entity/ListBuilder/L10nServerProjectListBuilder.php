<?php
declare(strict_types=1);

namespace Drupal\l10n_server\Entity\ListBuilder;

use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a list controller for the project entity type.
 */
class L10nServerProjectListBuilder extends EntityListBuilder {

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected DateFormatterInterface $dateFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type): self {
    $instance = parent::createInstance($container, $entity_type);
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

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
    $build['summary']['#markup'] = $this->t('Total projects: @total', [
      '@total' => $total,
    ]);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['uri'] = $this->t('URI');
    $header['connector'] = $this->t('Connector');
    $header['homepage'] = $this->t('Homepage');
    $header['releases'] = $this->t('Releases');
    $header['status'] = $this->t('Status');
    $header['last_parsed'] = $this->t('Last parsed');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\l10n_server\Entity\L10nServerProjectInterface $entity */
    $row['label'] = $entity->toLink();
    $row['uri'] = $entity->getUri();
    $row['connector'] = $entity->getConnectorModule();
    if ($link = $entity->getHomepage()) {
      $row['homepage'] = Link::fromTextAndUrl(
        Url::fromUri($link)->toString(),
        Url::fromUri($link)
      );
    }
    else {
      $row['homepage'] = $this->t('n/a');
    }
    $row['releases'] = Link::createFromRoute(t('Releases'), 'entity.l10n_server_project.releases', [
      'l10n_server_project' => $entity->id(),
    ]);
    $row['status'] = $entity->getStatus();
    $row['last_parsed'] = $entity->getLastParsed() ? $this->dateFormatter->format($entity->getLastParsed()) : '-';
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  protected function getTitle(): TranslatableMarkup {
    return $this->t('Projects and releases');
  }

}
