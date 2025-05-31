<?php

namespace Drupal\events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventConfigLogController extends ControllerBase
{
  protected $connection;

  public function __construct(Connection $connection)
  {
    $this->connection = $connection;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database')
    );
  }

  public function list()
  {
    $header = ['ID', 'Config', 'Value', 'By user'];
    $rows = [];
    $query = $this->connection->select('event_config_log', 'ecl');
    $query->leftJoin('users_field_data', 'u', 'ecl.user_id = u.uid');
    $query->fields('ecl', ['id', 'config', 'value']);
    $query->fields('u', ['name']);

    // Add pager with limit
    $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(10);

    $results = $query->execute();

    foreach ($results as $row) {
      $rows[] = [
        $row->id,
        $row->config,
        $row->value,
        $row->name ?: $this->t('Unknown user'),
      ];
    }

    return [
      '#type' => 'container',
      'log_table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No log found.'),
        '#attributes' => [
          'class' => ['my-custom-table'],
          'style' => 'width: 100%; border-collapse: collapse;text-align:center',
        ],
      ],
      'pager' => [
        '#type' => 'pager',
      ],
      '#attached' => [
        'library' => [
          'events/events.styles',
        ],
      ],
         '#cache' => [
        'max-age' => 0,  // disables caching for this page
      ],
    ];
  }
}
