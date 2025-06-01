<?php

namespace Drupal\categories\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;
class CategoryController extends ControllerBase
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



    $query = $connection->select('categories', 'c')
      ->fields('c', ['id', 'title', 'description'])->extend('Drupal\Core\Database\Query\PagerSelectExtender')
        ->limit(5);


    $results = $query->execute();

    // Step 3: Prepare table
    $header = ['ID', 'Title', 'Description', 'Operations'];
    $rows = [];

   foreach ($results as $row) {
      $rows[] = $this->buildEventRow($row);
    }

    // Step 4: Add Create Event link
    $add_category_link = Link::fromTextAndUrl($this->t('Add New category'), Url::fromUri('internal:/categories/add'))
      ->toRenderable();
    $add_category_link['#attributes'] = ['class' => ['button', 'button--primary'], 'style' => 'margin-bottom:15px;'];
    //latest events link

    return [

      'add_event' => $add_category_link,

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
    $edit_url = Url::fromRoute('categories.edit', ['id' => $row->id]);
    $delete_url = Url::fromRoute('categories.delete', ['id' => $row->id]);

    $edit_link = Link::fromTextAndUrl('Edit', $edit_url)->toRenderable();
    $delete_link = Link::fromTextAndUrl('Delete', $delete_url)->toRenderable();


    return [
      $row->id,
      $row->title,
      $row->description,

      [
        'data' => [
          '#type' => 'container',
          '#attributes' => ['class' => ['operations-links']],
          'separator1' => ['#markup' => ' | '],
          'edit' => $edit_link,
          'separator2' => ['#markup' => ' | '],
          'delete' => $delete_link,
        ],
      ],
    ];
  }

  public function delete($id)
  {
    $events = \Drupal::database()->select('events', 'e')
      ->fields('e', ['id'])
      ->condition('category_id', $id)
      ->execute()
      ->fetchAllKeyed();
    if (!empty($events)) {
      \Drupal::messenger()->addError($this->t('Cannot delete category with existing events.'));
      return $this->redirect('categories.list');
    }
    \Drupal::database()->delete('categories')
      ->condition('id', $id)
      ->execute();

    return $this->redirect('categories.list');
  }
}
