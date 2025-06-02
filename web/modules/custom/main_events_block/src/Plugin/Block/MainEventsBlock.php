<?php

namespace Drupal\main_events_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\node\Entity\Node;

// created to manage the block content type main_events to get the lates 5 events created from the content type main_events
/**
 * Provides a 'Main Events' Block.
 *
 * @Block(
 *   id = "main_events_block",
 *   admin_label = @Translation("Main Events Block"),
 *   category = @Translation("Custom")
 * )
 */
class MainEventsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    // Load 5 latest 'main_events' nodes ordered by end_date descending.
   $query = \Drupal::entityTypeManager()->getStorage('node')->getQuery()
  ->accessCheck(TRUE);
    $nids = $query->condition('status', 1)
      ->condition('type', 'main_events')
      ->sort('field_end_date', 'DESC')
      ->range(0, 5)
      ->execute();

    $nodes = Node::loadMultiple($nids);

    $items = [];
    foreach ($nodes as $node) {
      $title = $node->label();
      $url = $node->toUrl()->toString();
      $end_date = $node->get('field_end_date')->date ? $node->get('field_end_date')->date->format('Y-m-d') : '';

      $items[] = [
        '#markup' => "<div><a href='$url'>$title</a> <small>($end_date)</small></div>",
      ];
    }

    return [
      '#theme' => 'item_list',
      '#items' => $items,
      '#title' => $this->t('Latest Main Events'),
      '#cache' => [
        'tags' => ['node_list'],  // Invalidate cache when nodes are added/updated.
        'contexts' => ['url.path'], // Optional, depends if content varies per path.
        'max-age' => 0,         // Cache for 1 hour.
      ],
    ];
  }

}
