<?php
namespace Drupal\events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;

class EventController extends ControllerBase
{

  public function list()
  {
    $connection = \Drupal::database();
    $date_formatter = \Drupal::service('date.formatter');

    // Step 1: Read config from event_config
    $config = $connection->select('event_config', 'ec')
      ->fields('ec')
      ->range(0, 1)
      ->execute()
      ->fetchObject();
      $paginated_count = isset($config->events_per_page) ? (int) $config->events_per_page : 10;
      $show_old = isset($config->show_old) && $config->show_old;

      // Step 2: Build query
      $query = $connection->select('events', 'e')
      ->fields('e', ['id', 'title', 'description', 'start_date', 'end_date']);

      if (!$show_old) {
        $query->condition('end_date', \Drupal::time()->getCurrentTime(), '>=');
      }

      // Pagination
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
      ->limit($paginated_count);

      $results = $query->execute();

      // Step 3: Prepare table
      $header = ['ID', 'Title', 'Description', 'Start date', 'End date', 'Operations'];
      $rows = [];

      foreach ($results as $row) {
      $edit_url = Url::fromRoute('events.edit', ['id' => $row->id]);
      $delete_url = Url::fromRoute('events.delete', ['id' => $row->id]);
      $view_url = Url::fromRoute('events.view', ['id' => $row->id]);

      $edit_link = Link::fromTextAndUrl('Edit', $edit_url)->toRenderable();
      $delete_link = Link::fromTextAndUrl('Delete', $delete_url)->toRenderable();
      $view_link = Link::fromTextAndUrl('View', $view_url)->toRenderable();

      $rows[] = [
        $row->id,
        $row->title,
        $row->description,
        $date_formatter->format($row->start_date, 'short'),
        $date_formatter->format($row->end_date, 'short'),
        [
          'data' => [
            '#type' => 'container',
            '#attributes' => ['class' => ['operations-links']],
            'view' => $view_link,
            'separator1' => ['#markup' => ' | '],
            'edit' => $edit_link,
            'separator2' => ['#markup' => ' | '],
            'delete' => $delete_link,
          ],
        ],
      ];
    }

    // Step 4: Add Create Event link
    $add_event_link = Link::fromTextAndUrl($this->t('Add New Event'), Url::fromUri('internal:/events/add'))
    ->toRenderable();
    $add_event_link['#attributes'] = ['class' => ['button', 'button--primary'], 'style' => 'margin-bottom:15px;'];

    return [
     'add_event' => $add_event_link,
      'event_table' => [
        '#type' => 'table',
        '#header' => $header,
        '#rows' => $rows,
        '#empty' => $this->t('No events found.'),
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

  public function delete($id)
  {
    \Drupal::database()->delete('events')
      ->condition('id', $id)
      ->execute();

    return $this->redirect('events.list');
  }
  }
