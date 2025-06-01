<?php
namespace Drupal\events\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a form for filtering events by category.
 */
class EventFillterByCategory extends FormBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  public function __construct(Connection $database) {
    $this->database = $database;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'event_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $categories = $this->database->select('categories', 'c')
      ->fields('c', ['id', 'title'])
      ->execute()
      ->fetchAllKeyed();

    $selected = \Drupal::request()->query->get('category');

     $form['#method'] = 'get';

    $form['category'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by Category'),
      '#options' => ['' => $this->t('- Select -')] + $categories,
      '#default_value' => $selected,
      '#attributes' => [
        'onchange' => 'this.form.submit();',
      ],
      '#name' => 'category', 
    ];



    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }
}
