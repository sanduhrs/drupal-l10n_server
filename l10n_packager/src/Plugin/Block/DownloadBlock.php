<?php

declare(strict_types=1);

namespace Drupal\l10n_packager\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Url;
use Psr\Container\ContainerInterface;

/**
 * Provides a 'Localization packager downloads' Block.
 *
 * @Block(
 *   id = "l10n_packager_download",
 *   admin_label = @Translation("Localization packager download block"),
 *   category = @Translation("Localization"),
 * )
 */
final class DownloadBlock extends BlockBase {

  /**
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  private CurrentPathStack $currentPath;

  /**
   * {@inheritdoc}
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      CurrentPathStack $currentPath
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPath = $currentPath;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
      ContainerInterface $container,
      array $configuration,
      string $plugin_id,
      $plugin_definition
  ) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('path.current')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build():array {
    return [
      '#type' => 'link',
      '#title' => 'Download translations',
      '#url' => Url::fromUri('translate/downloads'),
    ];
  }

}
