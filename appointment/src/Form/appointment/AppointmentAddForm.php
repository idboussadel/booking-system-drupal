<?php

namespace Drupal\appointment\Form\appointment;

use DateTime;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\TempStore\TempStoreException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a multi-step form for appointment.
 */
final class AppointmentAddForm extends FormBase
{

  /**
   * The tempstore factory.
   *
   * @var PrivateTempStoreFactory
   */
  protected PrivateTempStoreFactory $tempStoreFactory;

  /**
   * The tempstore object.
   *
   * @var PrivateTempStore
   */
  protected PrivateTempStore $tempStore;

  /**
   * Constructs a new AppointmentAddForm.
   *
   * @param PrivateTempStoreFactory $tempStoreFactory
   *   The tempstore factory.
   */
  public function __construct(PrivateTempStoreFactory $tempStoreFactory)
  {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->tempStore = $tempStoreFactory->get('appointment_add_form');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'appointment_multi_step_form';
  }

  /**
   * {@inheritdoc}
   * @throws \DateMalformedStringException
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $step = $this->tempStore->get('step') ?? 1;
    $values = $this->tempStore->get('values') ?? [];

    try {
      $agencies = \Drupal::entityTypeManager()->getStorage('agency')->loadMultiple();
      $terms = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->loadByProperties(['vid' => 'appointment_type']);

      $user_storage = \Drupal::entityTypeManager()->getStorage('user');

    } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
      \Drupal::logger('appointment')->error($e->getMessage());
      $agencies = [];
      $terms = [];
    }

    $options = [];
    foreach ($terms as $term) {
      $options[] = ['id' => $term->id(), 'name' => $term->label()];
    }

    $users_data = [];


    if (!empty($values['selected_agency'])) {
      $query = \Drupal::entityTypeManager()->getStorage('user')->getQuery();
      $query->condition('field_agency', $values['selected_agency']);
      $query->condition('status', 1);
      $query->accessCheck(FALSE);

      $user_ids = $query->execute();
      $users = $user_storage->loadMultiple($user_ids);

      foreach ($users as $user) {
        $users_data[] = [
          'id' => $user->id(),
          'name' => $user->getAccountName(),
        ];
      }
    }

    $agency_options = [];
    foreach ($agencies as $agency) {
      $agency_options[] = [
        'id' => $agency->id(),
        'name' => $agency->label(),
        'address' => $agency->get('address')->value,
      ];
    }

    $working_hours = [];
    if (!empty($values['selected_agency'])) {
      // Load the selected agency entity using the ID.
      $selected_agency = \Drupal::entityTypeManager()->getStorage('agency')->load($values['selected_agency']);
      if ($selected_agency) {
        $agency_working_hours = $selected_agency->get('field_working_hours')->getValue();
        $working_hours['agency'] = $this->formatWorkingHours($agency_working_hours);
      }
    }

    if (!empty($values['selected_advisor'])) {
      // Load the selected advisor entity using the ID.
      $selected_advisor = \Drupal::entityTypeManager()->getStorage('user')->load($values['selected_advisor']);
      if ($selected_advisor) {
        $advisor_working_hours = $selected_advisor->get('field_working_hours')->getValue();
        $working_hours['advisor'] = $this->formatWorkingHours($advisor_working_hours);
      }
    }
    if (!empty($values['selected_advisor']&&!empty($values['selected_agency']))){
      $appointments = \Drupal::entityTypeManager()
        ->getStorage('appointment')
        ->loadByProperties([
          'agency' => $values['selected_agency'] ?? NULL,
          'adviser' => $values['selected_advisor'] ?? NULL,
        ]);

      $appointment_events = [];
      foreach ($appointments as $appointment) {
        $type = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->load($values['selected_type']);
        $appointment_events[] = [
          'start' => $appointment->get('start_date')->value,
          'end' => $appointment->get('end_date')->value,
          'title' => $type->label() . '-' .$appointment->get('customer_last_name')->value,
        ];
      }
      $form['#attached']['drupalSettings']['appointment']['existing_appointments'] = $appointment_events;
    }

    // Pass working hours to the frontend.
    $form['#attached']['drupalSettings']['appointment']['working_hours'] = $working_hours;

    $form['#prefix'] = '<div id="appointment-form-wrapper">';
    $form['#suffix'] = '</div>';

    switch ($step) {
      case 1:
        $form['step_1'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 1: Choose an agency'),
        ];

        $form['step_1']['selected_agency'] = [
          '#type' => 'hidden',
          '#attributes' => ['class' => 'selected_agency'],
          '#default_value' => $values['selected_agency'] ?? '',
        ];

        $form['step_1']['agency_list'] = [
          '#theme' => 'agency_selection',
          '#agencies' => $agency_options,
          '#selected_agency' => $values['selected_agency'] ?? '',
        ];

        $form['actions'] = [
          '#prefix' => '<div class="form-step1">',
          '#suffix' => '</div>',
        ];

        $form['actions']['next'] = $this->getNextButton();
        break;

      case 2:
        $form['step_2'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 2: Select an appointment type'),
        ];

        $form['step_2']['selected_type'] = [
          '#type' => 'hidden',
          '#attributes' => ['class' => 'selected_type'],
          '#default_value' => $values['selected_type'] ?? '',
        ];

        $form['step_2']['types_list'] = [
          '#theme' => 'appointment_type_selection',
          '#types' => $options,
          '#selected_type' => $values['selected_type'] ?? '',
        ];

        $form['actions'] = [
          '#prefix' => '<div class="form-actions">',
          '#suffix' => '</div>',
        ];

        $form['actions']['previous'] = $this->getPreviousButton();
        $form['actions']['next'] = $this->getNextButton();
        break;

      case 3:
        $form['step_3'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 3: Select an advisor'),
        ];

        $form['step_3']['selected_advisor'] = [
          '#type' => 'hidden',
          '#attributes' => ['class' => 'selected_advisor'],
          '#default_value' => $values['selected_advisor'] ?? '',
        ];

        $form['step_3']['advisor_list'] = [
          '#theme' => 'advisor_selection',
          '#advisors' => $users_data ?? [],
          '#selected_advisor' => $values['selected_advisor'] ?? '',
        ];

        $form['actions'] = [
          '#prefix' => '<div class="form-actions">',
          '#suffix' => '</div>',
        ];

        $form['actions']['previous'] = $this->getPreviousButton();
        $form['actions']['next'] = $this->getNextButton();
        break;

      case 4:
        $form['step_4'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 4: choisissez le jour et l\'heure de votre rendez-vous'),
        ];

        $form['step_4']['calendar'] = [
          '#markup' => '<div id="fullcalendar"></div>',
        ];

        $form['step_4']['start_date'] = [
          '#type' => 'hidden',
          '#default_value' => $values['start_date'] ?? '',
          '#attributes' => ['id' => 'selected-start-date'],
        ];

        $form['step_4']['end_date'] = [
          '#type' => 'hidden',
          '#default_value' => $values['end_date'] ?? '',
          '#attributes' => ['id' => 'selected-end-date'],
        ];

        $form['actions'] = [
          '#prefix' => '<div class="form-actions">',
          '#suffix' => '</div>',
        ];

        $form['actions']['previous'] = $this->getPreviousButton();
        $form['actions']['next'] = $this->getNextButton();
        break;

      case 5:
        $date_time_info = $this->extractDateTime($values['start_date'] ?? '', $values['end_date'] ?? '');

        $form['client_info_wrapper'] = [
          '#prefix' => '<div class="client-info-wrapper">',
          '#suffix' => '</div>',
        ];

        $form['client_info_wrapper']['rdv_info'] = [
          '#theme' => 'client_info_select',
          '#date' => $date_time_info['date'],
          '#start_time' => $date_time_info['start_time'],
          '#end_time' => $date_time_info['end_time'],
        ];

        $form['client_info_wrapper']['client_info'] = [
          '#prefix' => '<div class="client-info">',
          '#suffix' => '</div>',
        ];

        $form['client_info_wrapper']['client_info']['name_container'] = [
          '#type' => 'container',
          '#attributes' => ['class' => ['name-container']],
        ];

        $form['client_info_wrapper']['client_info']['name_container']['customer_first_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Prenom'),
          '#required' => TRUE,
          '#default_value' => $values['customer_first_name'] ?? '',
        ];

        $form['client_info_wrapper']['client_info']['name_container']['customer_last_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Nom'),
          '#required' => TRUE,
          '#default_value' => $values['customer_last_name'] ?? '',
        ];

        $form['client_info_wrapper']['client_info']['customer_email'] = [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#required' => TRUE,
          '#default_value' => $values['customer_email'] ?? '',
        ];

        $form['client_info_wrapper']['client_info']['customer_phone'] = [
          '#type' => 'tel',
          '#title' => $this->t('Phone'),
          '#required' => TRUE,
          '#default_value' => $values['customer_phone'] ?? '',
        ];

        $form['client_info_wrapper']['client_info']['accept_terms'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('En cochant cette case, j\'accepte et je reconnais avoir pris connaissance des conditions générales d\'utilisation.'),
          '#required' => TRUE,
          '#default_value' => $values['accept_terms'] ?? FALSE,
        ];

        $form['actions'] = [
          '#prefix' => '<div class="form-actions">',
          '#suffix' => '</div>',
        ];

        $form['actions']['previous'] = $this->getPreviousButton();
        $form['actions']['next'] = $this->getNextButton();
        break;
      case 6:
        $date_time_info = $this->extractDateTime($values['start_date'] ?? '', $values['end_date'] ?? '');

        $form['user'] = [
          '#prefix' => '<div class="profile">',
          '#suffix' => '</div>',
        ];

        $form['user']['title_container'] = [
          '#prefix' => '<div class="title-container">',
          '#suffix' => '</div>',
        ];

        $form['user']['title_container']['title'] = [
          '#markup' => '<h3 class="details-title">Profil de l\'utilisateur</h3>',
        ];

        $form['user']['title_container']['modifier_profile'] = [
          '#type' => 'submit',
          '#value' => $this->t('Modifier'),
          '#name' => 'modifier_profile',
          '#attributes' => ['class' => ['previous-btn']],
          '#submit' => ['::modifierProfile'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'appointment-form-wrapper',
            'effect' => 'fade',
          ],
        ];

        $form['user']['details'] = [
          '#theme' => 'user_details',
          '#rdv' => $values,
        ];

        $form['rdv'] = [
          '#prefix' => '<div class="rdv">',
          '#suffix' => '</div>',
        ];

        $form['rdv']['title_container'] = [
          '#prefix' => '<div class="title-container">',
          '#suffix' => '</div>',
        ];

        $form['rdv']['title_container']['date'] = [
          '#markup' => '<h3 class="details-title">Rendez-vous</h3>',
        ];

        $form['rdv']['title_container']['modifier_date'] = [
          '#type' => 'submit',
          '#value' => $this->t('Modifier'),
          '#name' => 'modifier_date',
          '#attributes' => ['class' => ['previous-btn']],
          '#submit' => ['::modifierDate'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'appointment-form-wrapper',
            'effect' => 'fade',
          ],
        ];

        $form['rdv']['details'] = [
          '#theme' => 'rdv_details',
          '#date' => $date_time_info['date'],
          '#start_time' => $date_time_info['start_time'],
          '#end_time' => $date_time_info['end_time'],
        ];

        $form['actions'] = [
          '#prefix' => '<div class="submit-action">',
          '#suffix' => '</div>',
        ];

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Submit'),
          '#attributes' => ['class' => ['next-btn']],
        ];
        break;
      case 7:
        $form['success'] = [
          '#theme' => 'success_appointment',
        ];
        $this->tempStore->delete('step');
        break;

    }

    $form['#attached']['library'][] = 'fullcalendar/fullcalendar';
    $form['#attached']['library'][] = 'appointment/appointment';

    return $form;
  }

  /**
   * Formats working hours for FullCalendar.
   */
  protected function formatWorkingHours(array $working_hours): array
  {
    $formatted_hours = [];
    foreach ($working_hours as $day => $hours) {
      if (!empty($hours['starthours']) && !empty($hours['endhours'])) {
        // Convert start and end times to "HH:mm" format
        $formatTime = function ($time) {
          $timeStr = str_pad($time, 4, '0', STR_PAD_LEFT); // Ensure 4 digits (e.g., 830 -> 0830)
          $hours = substr($timeStr, 0, 2);
          $minutes = substr($timeStr, 2, 2);
          return "{$hours}:{$minutes}";
        };

        $formatted_hours[] = [
          'daysOfWeek' => [($day + 8) % 7],
          'startTime' => $formatTime($hours['starthours']),
          'endTime' => $formatTime($hours['endhours']),
        ];
      }
    }
    return $formatted_hours;
  }


  /**
   * Returns the "Next" button configuration.
   */
  protected function getNextButton(): array
  {
    return [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#validate' => ['::validateStep'],
      '#submit' => ['::submitForm'],
      '#attributes' => ['class' => ['next-btn']],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'appointment-form-wrapper',
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Returns the "Previous" button configuration.
   */
  protected function getPreviousButton(): array
  {
    return [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#submit' => ['::submitPrevious'],
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['previous-btn']],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'appointment-form-wrapper',
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Extracts date, start time, and end time from start_date and end_date.
   *
   * @param string $start_date
   *   The start date in ISO 8601 format (e.g., 2025-03-19T08:00:00Z).
   * @param string $end_date
   *   The end date in ISO 8601 format (e.g., 2025-03-19T09:00:00Z).
   *
   * @return array
   *   An associative array containing:
   *   - date: The extracted date in the format "Thursday 22 April 2021".
   *   - start_time: The extracted start time (e.g., 08:00).
   *   - end_time: The extracted end time (e.g., 09:00).
   * @throws \DateMalformedStringException
   */
  function extractDateTime($start_date, $end_date): array
  {
    $date = '';
    $start_time = '';
    $end_time = '';

    if (!empty($start_date)) {
      $date_time = new DateTime($start_date);
      $date = $date_time->format('l j F Y');
      $start_time = $date_time->format('H:i');
    }

    if (!empty($end_date)) {
      $date_time = new DateTime($end_date);
      $end_time = $date_time->format('H:i');
    }

    return [
      'date' => $date,
      'start_time' => $start_time,
      'end_time' => $end_time,
    ];
  }

  /**
   * {@inheritdoc}
   * @throws TempStoreException
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void
  {
    $step = $this->tempStore->get('step') ?? 1;
    $values = $this->tempStore->get('values') ?? [];
    switch ($step) {
      case 1:
        $values['selected_agency'] = $form_state->getValue('selected_agency');
        $this->tempStore->set('values', $values);
        $this->tempStore->set('step', 2);
        break;

      case 2:
        $values['selected_type'] = $form_state->getValue('selected_type');
        $this->tempStore->set('values', $values);
        $this->tempStore->set('step', 3);
        break;

      case 3:
        $values['selected_advisor'] = $form_state->getValue('selected_advisor');
        $this->tempStore->set('values', $values);
        $this->tempStore->set('step', 4);
        break;

      case 4:
        $values['start_date'] = $form_state->getValue('start_date');
        $values['end_date'] = $form_state->getValue('end_date');
        $this->tempStore->set('values', $values);
        $this->tempStore->set('step', 5);
        break;

      case 5:
        $values['customer_first_name'] = $form_state->getValue('customer_first_name');
        $values['customer_last_name'] = $form_state->getValue('customer_last_name');
        $values['customer_email'] = $form_state->getValue('customer_email');
        $values['customer_phone'] = $form_state->getValue('customer_phone');
        $values['accept_terms'] = $form_state->getValue('accept_terms');

        $this->tempStore->set('values', $values);
        $this->tempStore->set('step', 6);
        break;

      case 6:
        $selected_type_entity = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->load($values['selected_type']);

        $start_date = new DateTime($values['start_date']);
        $formatted_date = $start_date->format('d-m-Y H:i');

        $title = $selected_type_entity->label() . ' RDV le ' . $formatted_date;
        try {
          $appointment = \Drupal::entityTypeManager()->getStorage('appointment')->create([
            'title' => $title,
            'agency' => $values['selected_agency'],
            'type' => $values['selected_type'],
            'adviser' => $values['selected_advisor'],
            'start_date' => $values['start_date'],
            'end_date' => $values['end_date'],
            'customer_first_name' => $values['customer_first_name'],
            'customer_last_name' => $values['customer_last_name'],
            'customer_email' => $values['customer_email'],
            'customer_phone' => $values['customer_phone'],
            'status' => 'pending',
          ]);

          $appointment->save();

          $this->tempStore->delete('values');
          $this->tempStore->set('step', 7);

          $form_state->setRedirect('appointment.success_page');

        } catch (\Exception $e) {
          \Drupal::logger('appointment')->error('Error creating appointment: @error', ['@error' => $e->getMessage()]);
          $this->messenger()->addError($this->t('An error occurred while creating your appointment. Please try again.'));
        }
        break;
    }

    $form_state->setRebuild();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AppointmentAddForm|static
  {
    return new static(
      $container->get('tempstore.private')
    );
  }

  /**
   * Custom validation handler for all steps.
   */
  public function validateStep(array &$form, FormStateInterface $form_state): void
  {
    $step = $this->tempStore->get('step') ?? 1;

    switch ($step) {
      case 1:
        $selected_agency = $form_state->getValue('selected_agency');
        if (empty($selected_agency)) {
          $form_state->setErrorByName('selected_agency', $this->t('Please select an agency.'));
        }
        break;

      case 2:
        $selected_type = $form_state->getValue('selected_type');
        if (empty($selected_type)) {
          $form_state->setErrorByName('selected_type', $this->t('Please select an appointment type.'));
        }
        break;

      case 3:
        $selected_advisor = $form_state->getValue('selected_advisor');
        if (empty($selected_advisor)) {
          $form_state->setErrorByName('selected_advisor', $this->t('Please select an advisor.'));
        }
        break;

      case 4:
        $start_date = $form_state->getValue('start_date');
        $end_date = $form_state->getValue('end_date');
        if (empty($start_date)) {
          $form_state->setErrorByName('start_date', $this->t('Please select a start date and time.'));
        } else if (empty($end_date)) {
          $form_state->setErrorByName('end_date', $this->t('Please select an enddate and time.'));
        }
        break;

      case 5:
        $customer_first_name = $form_state->getValue('customer_first_name');
        if (empty($customer_first_name)) {
          $form_state->setErrorByName('customer_first_name', $this->t('Please enter your first name.'));
        }

        $customer_last_name = $form_state->getValue('customer_last_name');
        if (empty($customer_last_name)) {
          $form_state->setErrorByName('customer_last_name', $this->t('Please enter your last name.'));
        }

        $customer_email = $form_state->getValue('customer_email');
        if (empty($customer_email)) {
          $form_state->setErrorByName('customer_email', $this->t('Please enter your email address.'));
        } elseif (!\Drupal::service('email.validator')->isValid($customer_email)) {
          $form_state->setErrorByName('customer_email', $this->t('Please enter a valid email address.'));
        }

        $customer_phone = $form_state->getValue('customer_phone');
        if (empty($customer_phone)) {
          $form_state->setErrorByName('customer_phone', $this->t('Please enter your phone number.'));
        } elseif (!preg_match('/^[0-9]+$/', $customer_phone)) {
          $form_state->setErrorByName('customer_phone', $this->t('Please enter a valid phone number (numbers only).'));
        }
        break;
    }
  }

  /**
   * Custom submit handler for Previous button.
   */
  public function submitPrevious(array &$form, FormStateInterface $form_state): void
  {
    $step = $this->tempStore->get('step');
    $this->tempStore->set('step', $step - 1);
    $form_state->setRebuild();
  }

  /**
   * Custom submit handler for modifer profile in the last step.
   */
  public function modifierProfile(array &$form, FormStateInterface $form_state): void
  {
    $this->tempStore->set('step', 5);
    $form_state->setRebuild();
  }

  /**
   * Custom submit handler for modifer date in the last step.
   */
  public function modifierDate(array &$form, FormStateInterface $form_state): void
  {
    $this->tempStore->set('step', 4);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for Step 1.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state): array
  {
    return $form;
  }
}
