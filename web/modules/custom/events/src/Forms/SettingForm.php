<?php

namespace Drupal\events\Forms;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SettingForm extends FormBase {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Dependency Injection of database connection.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'events_settings';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Fetch the settings from the custom table.
    $config = $this->database->select('event_config', 'ec')
      ->fields('ec')
      ->condition('id', 1)
      ->execute()
      ->fetchObject();

    $form['show_old'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show old events?'),
      '#default_value' => isset($config->show_old) ? (bool) $config->show_old : 1,
    ];

    $form['events_per_page'] = [
      '#type' => 'number',
      '#title' => $this->t('Events per page'),
      '#default_value' => $config->events_per_page ?? 5,
      '#min' => 1,
      '#required' => TRUE,
    ];
   $form['submit'] = [
      '#type' => 'submit',
      '#value' => $config->id ? $this->t('Update') : $this->t('Save'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
 /**
 * {@inheritdoc}
 */
public function submitForm(array &$form, FormStateInterface $form_state) {
  $config_data = [
    'show_old' => (int) $form_state->getValue('show_old'),
    'events_per_page' => $form_state->getValue('events_per_page'),
  ];

  $exists = $this->database->select('event_config', 'ec')
    ->fields('ec', ['id'])
    ->condition('id', 1)
    ->execute()
    ->fetchField();

  if ($exists) {
    // Update config
    $this->database->update('event_config')
      ->fields($config_data)
      ->condition('id', 1)
      ->execute();
  } else {
    // Insert config
    $this->database->insert('event_config')
      ->fields($config_data)
      ->execute();
  }

  // Log changes in event_config_log
  $current_user_id = \Drupal::currentUser()->id();
  foreach ($config_data as $key => $value) {
    $this->database->insert('event_config_log')
      ->fields([
        'config' => $key,
        'user_id' => $current_user_id,
        'value' => (string) $value,
         'created' => \Drupal::time()->getCurrentTime(),
      ])
      ->execute();
  }

  $this->messenger()->addStatus($this->t('Event configuration has been saved.'));
}

}
