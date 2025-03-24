<?php

namespace Drupal\appointment\Form\appointment;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a telephone search form for appointments.
 */
Final class AppointmentSearchForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a new AppointmentSearchForm.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'appointment_search_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['search'] = [
      '#prefix' => '<div class="search-form">',
      '#suffix' => '</div>',
    ];

    $form['search']['telephone'] = [
      '#type' => 'tel',
      '#title' => $this->t('Phone Number'),
      '#required' => TRUE,
      '#pattern' => '[0-9]{10}',
      '#attributes' => [
        'placeholder' => $this->t('Enter a phone number'),
      ],
      '#default_value' => $form_state->getValue('telephone', ''),
    ];

    $form['search']['actions'] = [
      '#type' => 'actions',
    ];

    $form['search']['actions']['submit'] = [
      '#type' => 'submit',
      '#attributes' => [
        'class' => ['search-btn'],
      ],
      '#value' => $this->t('Search'),
    ];

    $form['search']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetForm'],
      '#attributes' => [
        'class' => ['previous-btn'],
      ],
      '#limit_validation_errors' => [],
    ];

    \Drupal::logger('appointment')->notice('tele: ' . print_r($form_state->getValue('telephone'), TRUE));
    if ($phone = $form_state->getValue('telephone')) {
      $form['results'] = $this->buildResults($phone);
    }

    $form['#attached']['library'][] = 'appointment/appointment';

    return $form;
  }

  /**
   * Builds the results section of the form.
   */
  protected function buildResults($phone): array {
    $storage = $this->entityTypeManager->getStorage('appointment');
    $query = $storage->getQuery()
      ->accessCheck(TRUE)
      ->condition('customer_phone', $phone)
      ->sort('start_date', 'DESC');

    $appointment_ids = $query->execute();

    // Debug output - check if we're finding appointments
    \Drupal::logger('appointment')->notice('Found appointment IDs: ' . print_r($appointment_ids, TRUE));

    if (empty($appointment_ids)) {
      return [
        '#markup' => '<div class="no-appointments">' . $this->t('Aucun rendez-vous trouvé.') . '</div>',
      ];
    }

    $appointments = $storage->loadMultiple($appointment_ids);
    $items = [];

    foreach ($appointments as $appointment) {
      $start_date = $appointment->get('start_date')->value;
      $formatted_date = \Drupal::service('date.formatter')->format(strtotime($start_date), 'custom', 'd/m/Y H:i');

      $items[] = [
        'entity' => $appointment,
        'formatted_date' => $formatted_date,
        'agency' => $appointment->get('agency')->entity ? $appointment->get('agency')->entity->label() : '',
        'adviser' => $appointment->get('adviser')->entity ? $appointment->get('adviser')->entity->label() : '',
        'type' => $appointment->get('type')->entity ? $appointment->get('type')->entity->label() : '',
        'edit_link' => $appointment->toUrl('edit-form')->toString(),
        'delete_link' => $appointment->toUrl('delete-form')->toString(),
      ];
    }

    return [
      '#theme' => 'appointment_results',
      '#items' => $items,
      '#attached' => [
        'library' => ['appointment/appointment'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Rebuild the form to show results
    $form_state->setRebuild();
  }

  /**
   * Resets the form.
   */
  public function resetForm(array &$form, FormStateInterface $form_state) {
    $form_state->setValue('telephone', '');
    $form_state->setUserInput([]);
    $form_state->setRebuild();
  }
}
