<?php

namespace Drupal\events\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
class EventController extends ControllerBase
{
  protected $dateFormatter;
    public function __construct() {
      $this->dateFormatter = \Drupal::service('date.formatter');
    }
   public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('database'),
            $container->get('form_builder'),
            $container->get('date.formatter'),
            $container->get('file_url_generator')
        );
    }
  public function list()
  {
    $connection = \Drupal::database();
   
    $request = \Drupal::request();
    $latest_only = $request->query->get('latest');
    $query = $connection->select('events', 'e')
      ->fields('e', ['id', 'title', 'description', 'category_name', 'start_date', 'end_date']);
    if ($latest_only) {
      // Show latest 5 events based on start_date DESC
      $query->orderBy('start_date', 'DESC');
      $query->range(0, 5);
    } else {


      $selected_category = $request->query->get('category');
      // Step 1: Read config from event_config
      $config = $connection->select('event_config', 'ec')
        ->fields('ec')
        ->range(0, 1)
        ->execute()
        ->fetchObject();
      $paginated_count = isset($config->events_per_page) ? (int) $config->events_per_page : 10;
      $show_old = isset($config->show_old) && $config->show_old;

      // Step 2: Build query

      if ($selected_category) {
        $query->condition('category_id', $selected_category);
      }
      if (!$show_old) {
        $query->condition('end_date', \Drupal::time()->getCurrentTime(), '>=');
      }

      // Pagination
      $query = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit($paginated_count);
      $query->addTag('events_list_category_filter');
      $query->addMetaData('pager_query_params', \Drupal::request()->query->all());
    }
    $results = $query->execute();

    // Step 3: Prepare table
    $header = ['ID', 'Title', 'Description', 'Category', 'Start date', 'End date', 'Operations'];
    $rows = [];

   foreach ($results as $row) {
      $rows[] = $this->buildEventRow($row);
    }

    // Step 4: Add Create Event link
    $add_event_link = Link::fromTextAndUrl($this->t('Add New Event'), Url::fromUri('internal:/events/add'))
      ->toRenderable();
    $add_event_link['#attributes'] = ['class' => ['button', 'button--primary'], 'style' => 'margin-bottom:15px;'];
    //latest events link
    $latest_event_link = Link::fromTextAndUrl(
      $this->t('Latest Events'),
      Url::fromUri('internal:/events', ['query' => ['latest' => 1]])
    )->toRenderable();
    $latest_event_link['#attributes'] = ['class' => ['button', 'button--secondary'], 'style' => 'margin-bottom:15px;'];

    $filter_form = \Drupal::formBuilder()->getForm('Drupal\events\Forms\EventFillterByCategory');
    return [
      'filter_actions' => $filter_form,
      'add_event' => $add_event_link,
      'latest_events' => $latest_event_link,
      'title' => [
        '#markup' => $latest_only ? '<h2>Latest 5 Events</h2>' : '<h2>All Events</h2>',
      ],
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
    protected function buildEventRow(object $row): array {
    $edit_url = Url::fromRoute('events.edit', ['id' => $row->id]);
    $delete_url = Url::fromRoute('events.delete', ['id' => $row->id]);
    $view_url = Url::fromRoute('events.view', ['id' => $row->id]);

    $edit_link = Link::fromTextAndUrl('Edit', $edit_url)->toRenderable();
    $delete_link = Link::fromTextAndUrl('Delete', $delete_url)->toRenderable();
    $view_link = Link::fromTextAndUrl('View', $view_url)->toRenderable();

    return [
      $row->id,
      $row->title,
      $row->description,
      $row->category_name,
      $this->dateFormatter->format($row->start_date, 'short'),
      $this->dateFormatter->format($row->end_date, 'short'),
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

  public function view($id)
  {
    $connection = \Drupal::database();
    $date_formatter = \Drupal::service('date.formatter');

    $event = $connection->select('events', 'e')
      ->fields('e', ['id', 'title', 'description', 'category_name', 'start_date', 'end_date'])
      ->condition('id', $id)
      ->execute()
      ->fetchObject();

    if (!$event) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }
    $event->start_date_formatted = $date_formatter->format($event->start_date, 'short');
    $event->end_date_formatted = $date_formatter->format($event->end_date, 'short');

    $image_records = $connection->select('event_images', 'ei')
      ->fields('ei', ['id', 'image_fid'])
      ->condition('event_id', $id)
      ->execute()
      ->fetchAll();
    $images = [];

    $file_url_generator = \Drupal::service('file_url_generator');

    foreach ($image_records as $record) {
      $file = File::load($record->image_fid);
      if ($file) {
        $images[] = $file_url_generator->generateAbsoluteString($file->getFileUri());
      }
    }


    return [
      '#theme' => 'event_view',
      '#event' => $event,
      '#images' => $images,
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
