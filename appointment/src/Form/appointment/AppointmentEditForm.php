<?php

namespace Drupal\appointment\Form\appointment;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TempStore\PrivateTempStore;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Component\Utility\Random;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use DateTime;

/**
 * Provides a form for editing appointments.
 */
final class AppointmentEditForm extends FormBase {

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
   * The mail manager.
   *
   * @var MailManagerInterface
   */
  protected MailManagerInterface $mailManager;

  /**
   * The language manager.
   *
   * @var LanguageManagerInterface
   */
  protected LanguageManagerInterface $languageManager;

  /**
   * Constructs a new AppointmentEditForm.
   *
   * @param PrivateTempStoreFactory $tempStoreFactory
   *   The tempstore factory.
   * @param MailManagerInterface $mailManager
   *   The mail manager.
   * @param LanguageManagerInterface $languageManager
   *   The language manager.
   */
  public function __construct(
    PrivateTempStoreFactory $tempStoreFactory,
    MailManagerInterface $mailManager,
    LanguageManagerInterface $languageManager
  ) {
    $this->tempStoreFactory = $tempStoreFactory;
    $this->tempStore = $tempStoreFactory->get('appointment_edit_form');
    $this->mailManager = $mailManager;
    $this->languageManager = $languageManager;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'appointment_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $appointment = NULL): array {
    $step = $this->tempStore->get('step') ?? 1;
    $values = $this->tempStore->get('values') ?? [];


    if (is_numeric($appointment)) {
      try {
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($appointment);

        if (!$appointment) {
          throw new \Exception('Appointment not found');
        }

        // Initialize tempstore if empty
        if (empty($values['appointment_id'])) {
          $values['appointment_id'] = $appointment->id();
          $this->tempStore->set('values', $values);
        }
      } catch (\Exception $e) {
        \Drupal::logger('appointment')->error('Error loading appointment: @error', ['@error' => $e->getMessage()]);
        $form['error'] = [
          '#markup' => $this->t('Invalid appointment.'),
        ];
        return $form;
      }
    }

    // Now safely load from tempstore
    $appointment = \Drupal::entityTypeManager()
      ->getStorage('appointment')
      ->load($values['appointment_id']);

    // Get agency and advisor from the appointment
    $agency_id = $appointment->get('agency')->target_id;
    $advisor_id = $appointment->get('adviser')->target_id;

    $working_hours = [];
    $appointment_events = [];

    // Load agency working hours
    if (!empty($agency_id)) {
      $agency = \Drupal::entityTypeManager()->getStorage('agency')->load($agency_id);
      if ($agency && $agency->hasField('field_working_hours')) {
        $agency_working_hours = $agency->get('field_working_hours')->getValue();
        if (!empty($agency_working_hours)) {
          $working_hours['agency'] = $this->formatWorkingHours($agency_working_hours);
        }
      }
    }

    // Load advisor working hours
    if (!empty($advisor_id)) {
      $advisor = \Drupal::entityTypeManager()->getStorage('user')->load($advisor_id);
      if ($advisor && $advisor->hasField('field_working_hours')) {
        $advisor_working_hours = $advisor->get('field_working_hours')->getValue();
        if (!empty($advisor_working_hours)) {
          $working_hours['advisor'] = $this->formatWorkingHours($advisor_working_hours);
        }
      }
    }

    // Load existing appointments for this agency/advisor
    $appointments = \Drupal::entityTypeManager()
      ->getStorage('appointment')
      ->loadByProperties([
        'agency' => $agency_id,
        'adviser' => $advisor_id,
      ]);

    foreach ($appointments as $appt) {
      $type = \Drupal::entityTypeManager()
        ->getStorage('taxonomy_term')
        ->load($appt->get('type')->target_id);
      $appointment_events[] = [
        'start' => $appt->get('start_date')->value,
        'end' => $appt->get('end_date')->value,
        'title' => $type ? $type->label() . '-' . $appt->get('customer_last_name')->value : 'Appointment',
      ];
    }

    $form['#attached']['drupalSettings']['appointment'] = [
      'working_hours' => $working_hours,
      'existing_appointments' => $appointment_events,
      'default_start_date' => $appointment->get('start_date')->value,
      'default_end_date' => $appointment->get('end_date')->value,
    ];

    // Load appointment if ID is passed
    if (is_numeric($appointment)) {
      try {
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($appointment);

        if (!$appointment) {
          throw new \Exception('Appointment not found');
        }
      } catch (\Exception $e) {
        \Drupal::logger('appointment')->error('Error loading appointment: @error', ['@error' => $e->getMessage()]);
        $form['error'] = [
          '#markup' => $this->t('Invalid appointment.'),
        ];
        return $form;
      }
    }

    if ($step === 1) {
      $values['appointment_id'] = $appointment->id();
      $this->tempStore->set('values', $values);

      // Generate and send verification code automatically
      $random = new Random();
      $code = $random->name(6, TRUE);
      $this->tempStore->set('verification_code', $code);

      // Send to the appointment's email
      $email = $appointment->get('customer_email')->value;
      $this->sendVerificationEmail($email, $code);

      // Immediately go to step 2 (verification)
      $this->tempStore->set('step', 2);
      $step = 2; // Update current step for this build
    }

    $form['#prefix'] = '<div id="appointment-edit-form-wrapper">';
    $form['#suffix'] = '</div>';

    switch ($step) {
      case 2:
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($values['appointment_id']);

        $form['step_2'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Verification Required'),
        ];

        $form['step_2']['description'] = [
          '#markup' => '<div class="verification-description"><p>' .
            $this->t('A verification code has been sent to: <strong>@email</strong>',
              ['@email' => $appointment->get('customer_email')->value]) . '</p></div>',
        ];

        $form['step_2']['verification_code'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Enter Verification Code'),
          '#required' => TRUE,
          '#attributes' => [
            'placeholder' => $this->t('6-digit code'),
            'autocomplete' => 'off',
          ],
        ];

        $form['actions'] = [
          '#type' => 'actions',
          '#attributes' => ['class' => ['verification-actions']],
        ];

        $form['actions']['resend'] = [
          '#type' => 'submit',
          '#value' => $this->t('Resend Code'),
          '#submit' => ['::resendVerificationCode'],
          '#attributes' => ['class' => ['resend-btn']],
          '#limit_validation_errors' => [],
        ];

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Verify & Continue'),
          '#attributes' => ['class' => ['verify-btn']],
        ];
        break;

      case 3:
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($values['appointment_id']);

        $date_time_info = $this->extractDateTime($appointment->get('start_date')->value ?? '', $appointment->get('end_date')->value ?? '');

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
          '#title' => $this->t('First Name'),
          '#required' => TRUE,
          '#default_value' => $appointment->get('customer_first_name')->value,
        ];

        $form['client_info_wrapper']['client_info']['name_container']['customer_last_name'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Last Name'),
          '#required' => TRUE,
          '#default_value' => $appointment->get('customer_last_name')->value,
        ];

        $form['client_info_wrapper']['client_info']['customer_email'] = [
          '#type' => 'email',
          '#title' => $this->t('Email'),
          '#required' => TRUE,
          '#default_value' => $appointment->get('customer_email')->value,
        ];

        $form['client_info_wrapper']['client_info']['customer_phone'] = [
          '#type' => 'tel',
          '#title' => $this->t('Phone'),
          '#required' => TRUE,
          '#default_value' => $appointment->get('customer_phone')->value,
        ];

        $form['client_info_wrapper']['client_info']['accept_terms'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('By checking this box, I accept and acknowledge that I have read the Terms and Conditions.'),
          '#required' => TRUE,
          '#default_value' => TRUE,
        ];

        $form['actions'] = [
          '#prefix' => '<div class="form-step1">',
          '#suffix' => '</div>',
        ];

        $form['actions']['next'] = [
          '#type' => 'submit',
          '#value' => $this->t('Next'),
          '#attributes' => ['class' => ['next-btn']],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'appointment-edit-form-wrapper',
            'effect' => 'fade',
          ],
        ];
        break;

      case 4:

        $form['step_4'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Step 4: Choose the day and time for your appointment'),
        ];

        $form['step_4']['calendar'] = [
          '#markup' => '<div id="fullcalendar"></div>',
        ];

        $form['step_4']['start_date'] = [
          '#type' => 'hidden',
          '#default_value' => $values['start_date'] ?? $appointment->get('start_date')->value,
          '#attributes' => ['id' => 'selected-start-date'],
        ];

        $form['step_4']['end_date'] = [
          '#type' => 'hidden',
          '#default_value' => $values['end_date'] ?? $appointment->get('end_date')->value,
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
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($values['appointment_id']);

        $date_time_info = $this->extractDateTime($appointment->get('start_date')->value ?? '', $appointment->get('end_date')->value ?? '');

        $form['user'] = [
          '#prefix' => '<div class="profile">',
          '#suffix' => '</div>',
        ];

        $form['user']['title_container'] = [
          '#prefix' => '<div class="title-container">',
          '#suffix' => '</div>',
        ];

        $form['user']['title_container']['title'] = [
          '#markup' => '<h3 class="details-title">User Profile</h3>',
        ];

        $form['user']['title_container']['modifier_profile'] = [
          '#type' => 'submit',
          '#value' => $this->t('Edit'),
          '#name' => 'modifier_profile',
          '#attributes' => ['class' => ['previous-btn']],
          '#submit' => ['::modifierProfile'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'appointment-edit-form-wrapper',
            'effect' => 'fade',
          ],
        ];

        $form['user']['details'] = [
          '#theme' => 'user_details',
          '#rdv' => [
            'customer_first_name' => $values['customer_first_name'] ?? $appointment->get('customer_first_name')->value,
            'customer_last_name' => $values['customer_last_name'] ?? $appointment->get('customer_last_name')->value,
            'customer_email' => $values['customer_email'] ?? $appointment->get('customer_email')->value,
            'customer_phone' => $values['customer_phone'] ?? $appointment->get('customer_phone')->value,
          ],
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
          '#markup' => '<h3 class="details-title">Appointment Details</h3>',
        ];

        $form['rdv']['title_container']['modifier_date'] = [
          '#type' => 'submit',
          '#value' => $this->t('Edit'),
          '#name' => 'modifier_date',
          '#attributes' => ['class' => ['previous-btn']],
          '#submit' => ['::modifierDate'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'appointment-edit-form-wrapper',
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

      case 6:
        $form['success'] = [
          '#theme' => 'success_appointment',
          '#title' => 'YOUR APPOINTMENT HAS BEEN SUCCESSFULLY UPDATED',
          '#description' => 'You can modify your appointment by providing your phone number.',
          '#appointment_id' => $values['appointment_id'],
        ];

        $this->tempStore->delete('step');
        $this->tempStore->delete('values');
        break;
    }

    $form['#attached']['library'][] = 'appointment/fullcalendar-update';
    $form['#attached']['library'][] = 'fullcalendar/fullcalendar';
    $form['#attached']['library'][] = 'appointment/appointment';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void
  {
    $step = $this->tempStore->get('step') ?? 1;

    switch ($step) {
      case 2:
        // Validate verification code
        $stored_code = $this->tempStore->get('verification_code');
        $entered_code = $form_state->getValue('verification_code');

        if (empty($entered_code)) {
          $form_state->setErrorByName('verification_code', $this->t('Please enter the verification code.'));
        }
        elseif ($entered_code !== $stored_code) {
          $form_state->setErrorByName('verification_code', $this->t('The verification code is incorrect.'));
        }
        break;

      case 3:
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
        }
        elseif (!\Drupal::service('email.validator')->isValid($customer_email)) {
          $form_state->setErrorByName('customer_email', $this->t('Please enter a valid email address.'));
        }

        $customer_phone = $form_state->getValue('customer_phone');
        if (empty($customer_phone)) {
          $form_state->setErrorByName('customer_phone', $this->t('Please enter your phone number.'));
        }
        elseif (!preg_match('/^[0-9]+$/', $customer_phone)) {
          $form_state->setErrorByName('customer_phone', $this->t('Please enter a valid phone number (numbers only).'));
        }
        break;

      case 4:
        $start_date = $form_state->getValue('start_date');
        $end_date = $form_state->getValue('end_date');

        if (empty($start_date) || empty($end_date)) {
          $form_state->setErrorByName('calendar', $this->t('Please select a time slot for your appointment.'));
        }
        break;

    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $step = $this->tempStore->get('step') ?? 1;
    $values = $this->tempStore->get('values') ?? [];

    switch ($step) {
      case 2:
        // Code verified, proceed to edit form
        $this->tempStore->set('step', 3);
        break;

      case 3:
        // Save client info changes
        $values['customer_first_name'] = $form_state->getValue('customer_first_name');
        $values['customer_last_name'] = $form_state->getValue('customer_last_name');
        $values['customer_email'] = $form_state->getValue('customer_email');
        $values['customer_phone'] = $form_state->getValue('customer_phone');

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
        // Final submission - update appointment
        try {
          $appointment = \Drupal::entityTypeManager()
            ->getStorage('appointment')
            ->load($values['appointment_id']);

          $appointment->set('customer_first_name', $values['customer_first_name']);
          $appointment->set('customer_last_name', $values['customer_last_name']);
          $appointment->set('customer_email', $values['customer_email']);
          $appointment->set('customer_phone', $values['customer_phone']);

          if (!empty($values['start_date'])) {
            $appointment->set('start_date', $values['start_date']);
          }
          if (!empty($values['end_date'])) {
            $appointment->set('end_date', $values['end_date']);
          }

          $appointment->save();

          $this->tempStore->set('step', 6);
        }
        catch (\Exception $e) {
          \Drupal::logger('appointment')->error('Error updating appointment: @error', ['@error' => $e->getMessage()]);
          $this->messenger()->addError($this->t('An error occurred while updating your appointment. Please try again.'));
        }
        break;
    }

    $form_state->setRebuild();
  }

  /**
   * Sends verification email with code.
   */
  protected function sendVerificationEmail($email, $code): void {
    $params = [
      'subject' => $this->t('Your appointment verification code'),
      'body' => [
        '#theme' => 'verification_email',
        '#code' => $code,
      ],
    ];

    // Ensure the body is rendered
    $renderer = \Drupal::service('renderer');
    $params['body'] = $renderer->renderPlain($params['body']);

    $this->mailManager->mail(
      'appointment',
      'verification_code',
      $email,
      $this->languageManager->getDefaultLanguage()->getId(),
      $params,
      NULL,
      TRUE
    );
  }

  /**
   * Resend verification code.
   */
  public function resendVerificationCode(array &$form, FormStateInterface $form_state): void
  {
    $code = $this->tempStore->get('verification_code');
    $values = $this->tempStore->get('values');

    $appointment = \Drupal::entityTypeManager()
      ->getStorage('appointment')
      ->load($values['appointment_id']);

    $email = $appointment->get('customer_email')->value;
    $this->sendVerificationEmail($email, $code);

    $this->messenger()->addStatus($this->t('A new verification code has been sent to your email address.'));
    $form_state->setRebuild();
  }

  /**
   * Custom submit handler for modifier profile.
   */
  public function modifierProfile(array &$form, FormStateInterface $form_state): void
  {
    $this->tempStore->set('step', 3);
    $form_state->setRebuild();
  }

  /**
   * Custom submit handler for modifier date.
   */
  public function modifierDate(array &$form, FormStateInterface $form_state): void
  {
    $this->tempStore->set('step', 4);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state): array
  {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): AppointmentEditForm|static
  {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager')
    );
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
   *
   * @throws \DateMalformedStringException
   */
  protected function extractDateTime($start_date, $end_date): array {
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
   * Returns the "Next" button configuration.
   */
  protected function getNextButton(): array {
    return [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#validate' => ['::validateForm'],
      '#submit' => ['::submitForm'],
      '#attributes' => ['class' => ['next-btn']],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'appointment-edit-form-wrapper',
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Returns the "Previous" button configuration.
   */
  protected function getPreviousButton(): array {
    return [
      '#type' => 'submit',
      '#value' => $this->t('Previous'),
      '#submit' => ['::submitPrevious'],
      '#limit_validation_errors' => [],
      '#attributes' => ['class' => ['previous-btn']],
      '#ajax' => [
        'callback' => '::ajaxCallback',
        'wrapper' => 'appointment-edit-form-wrapper',
        'effect' => 'fade',
      ],
    ];
  }

  /**
   * Custom submit handler for Previous button.
   */
  public function submitPrevious(array &$form, FormStateInterface $form_state): void {
    $step = $this->tempStore->get('step');
    $this->tempStore->set('step', $step - 1);
    $form_state->setRebuild();
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
}
