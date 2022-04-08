<?php

declare(strict_types=1);

namespace Drupal\l10n_packager\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Path\CurrentPathStack;
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
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPathStack $currentPath) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentPath = $currentPath;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container, array $configuration, string $plugin_id, $plugin_definition) {
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
  public function build(): array {
//    if (\Drupal::currentUser()->hasPermission('access localization community')) {
//      $current_path = $this->currentPath->getPath();
//      if (arg(0) == 'translate') {
//        $arg1 = arg(1);
//        $arg2 = arg(2);
//        $arg3 = arg(3);
//        if ($arg1 == 'projects' && !empty($arg2) && empty($arg3)) {
//          return array(
//            'content' => l('<span>' . t('Download translations') . '</span>', 'translate/downloads', array('html' => TRUE, 'query' => array('project' => $arg2), 'attributes' => array('class' => array('link-button')))),
//          );
//        }
//      }
//    }

    return [
      '#markup' => $this->t('Hello, World!'),
    ];
  }

}
