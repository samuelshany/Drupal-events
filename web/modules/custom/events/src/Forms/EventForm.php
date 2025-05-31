<?php

namespace Drupal\events\Forms;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class EventForm extends FormBase
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $connection;

  /**
   * Constructs a new EventForm.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   */
  public function __construct(Connection $connection)
  {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'events_event_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array
  {
    $title = '';
    $description = '';
    $start_date = NULL;
    $end_date = NULL;
    $categories = $this->connection->select('categories', 'c')
      ->fields('c', ['id', 'name'])
      ->execute();
    $category_options = [];
    foreach ($categories as $category) {
      $category_options[$category->id] = $category->name;
    }
    $default_images = [];
    $selected_category_id = NULL;
    if ($id) {
      $record = $this->connection->select('events', 'e')
        ->fields('e')
        ->condition('id', $id)
        ->execute()
        ->fetchObject();

      if ($record) {
        $form['id'] = [
          '#type' => 'hidden',
          '#value' => $record->id,
        ];

        $title = $record->title;
        $description = $record->description;
        $start_date = $record->start_date ? DrupalDateTime::createFromTimestamp($record->start_date) : NULL;
        $end_date = $record->end_date ? DrupalDateTime::createFromTimestamp($record->end_date) : NULL;

        // Load image fids for this event
        $query = $this->connection->select('event_images', 'ei')
          ->fields('ei', ['image_fid'])
          ->condition('event_id', $id);
        $fids = $query->execute()->fetchCol();

        foreach ($fids as $fid) {
          $file = File::load($fid);
          if ($file) {
            // Set as permanent so Drupal will display them
            $file->setPermanent();
            $file->save();
            $default_images[] = $fid;
          }
        }
        $selected_category_id = $record->category_id;
      }
    }


    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#required' => TRUE,
      '#default_value' => $title,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#default_value' => $description,
    ];

    $form['start_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Start Date'),
      '#required' => TRUE,
      '#default_value' => $start_date,
    ];

    $form['end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('End Date'),
      '#required' => TRUE,
      '#default_value' => $end_date,
    ];
    $form['images'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Images'),
      '#upload_location' => 'public://event_images/',
      '#multiple' => TRUE,
      '#default_value' => $default_images,
      '#upload_validators' => [
        'file_validate_extensions' => ['jpg jpeg png'],
      ],
      '#description' => $this->t('Allowed extensions: jpg, jpeg, png'),
    ];
    $form['category_id'] = [
  '#type' => 'select',
  '#title' => $this->t('Category'),
  '#required' => TRUE,
  '#options' => $category_options,
  '#default_value' => $selected_category_id,
];

    $form['#tree'] = TRUE;
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $id ? $this->t('Update') : $this->t('Save'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {

    $start_date = $form_state->getValue('start_date');
    $end_date = $form_state->getValue('end_date');

    $now = \Drupal::time()->getCurrentTime();
    $start_of_today = strtotime('today', $now);

    if ($start_date instanceof DrupalDateTime) {
      $start_timestamp = $start_date->getTimestamp();
    } else {
      $start_timestamp = strtotime($start_date);
    }

    if ($end_date instanceof DrupalDateTime) {
      $end_timestamp = $end_date->getTimestamp();
    } else {
      $end_timestamp = strtotime($end_date);
    }

    if ($start_timestamp < $start_of_today) {
      $form_state->setErrorByName('start_date', $this->t('Start date cannot be in the past.'));
    }

    if ($end_timestamp <= $start_timestamp) {
      $form_state->setErrorByName('end_date', $this->t('End date must be after the start date.'));
    }
    $file_ids = $form_state->getValue('images');
    if ($file_ids) {
      foreach ($file_ids as $fid) {
        $file = File::load($fid);
        if ($file) {
          $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);
          if (!in_array(strtolower($extension), ['jpg', 'jpeg', 'png'])) {
            $form_state->setErrorByName('images', $this->t('Only JPG and PNG images are allowed.'));
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $current_user = \Drupal::currentUser();
    $time = \Drupal::time()->getCurrentTime();

    /** @var \Drupal\Core\Datetime\DrupalDateTime $start_date */
    $start_date = $form_state->getValue('start_date');
    /** @var \Drupal\Core\Datetime\DrupalDateTime $end_date */
    $end_date = $form_state->getValue('end_date');

    $fields = [
      'title' => $form_state->getValue('title'),
      'description' => $form_state->getValue('description'),
      'start_date' => $start_date instanceof DrupalDateTime ? $start_date->getTimestamp() : strtotime($start_date),
      'end_date' => $end_date instanceof DrupalDateTime ? $end_date->getTimestamp() : strtotime($end_date),
      'changed' => $time,
      'updated_by' => $current_user->id(),
      'category_id' => $form_state->getValue('category_id'),
    ];

    if ($id = $form_state->getValue('id')) {
      $this->connection->update('events')
        ->fields($fields)
        ->condition('id', $id)
        ->execute();
      $results = $this->connection->select('event_images', 'ei')
        ->fields('ei', ['image_fid'])
        ->condition('event_id', $id)
        ->execute()
        ->fetchCol();

      if (!empty($results)) {
        foreach ($results as $fid) {
          // Delete file entity (removes physical file too)
          if ($file = File::load($fid)) {
            $file->delete();
          }
        }

        // Delete all DB rows in one go (outside loop)
        $this->connection->delete('event_images')
          ->condition('event_id', $id)
          ->execute();
      }
      $event_id = $id;
    } else {
      $fields['created'] = $time;
      $fields['created_by'] = $current_user->id();
      $event_id = $this->connection->insert('events')
        ->fields($fields)
        ->execute();
    }

    $image_fids = $form_state->getValue('images');
    if (!empty($image_fids)) {
      foreach ($image_fids as $fid) {
        $file = File::load($fid);
        if ($file) {
          $file->setPermanent();
          $file->save();

          // Insert into event_images table
          $this->connection->insert('event_images')
            ->fields([
              'event_id' => $event_id,
              'image_fid' => $fid,
              'created' => $time,
            ])
            ->execute();
        }
      }
    }
    $form_state->setRedirectUrl(Url::fromRoute('events.list'));
  }
}
