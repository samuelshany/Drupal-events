<?php

namespace Drupal\categories\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CategoryForm extends FormBase
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
    return 'categories_category_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL): array
  {
    $title = '';
    $description = '';

    if ($id) {
      $record = $this->connection->select('categories', 'c')
        ->fields('c')
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
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $id ? $this->t('Update') : $this->t('Save'),
    ];

    return $form;
  }



  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {

    $time = \Drupal::time()->getCurrentTime();
    $fields = [
      'title' => $form_state->getValue('title'),
      'description' => $form_state->getValue('description'),
      'changed' => $time,
    ];

    if ($id = $form_state->getValue('id')) {
      $this->connection->update('categories')
        ->fields($fields)
        ->condition('id', $id)
        ->execute();
    } else {
      $fields['created'] = $time;

      $event_id = $this->connection->insert('categories')
        ->fields($fields)
        ->execute();
    }
    $form_state->setRedirectUrl(Url::fromRoute('categories.list'));
  }
}
