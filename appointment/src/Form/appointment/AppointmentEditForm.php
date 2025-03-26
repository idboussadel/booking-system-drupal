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

    // Store appointment ID if we're just starting
    if ($step === 1 && empty($values['appointment_id']) && $appointment instanceof \Drupal\appointment\Entity\Appointment) {
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
        // Verification code step
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($values['appointment_id']);

        $form['step_2'] = [
          '#type' => 'fieldset',
          '#title' => $this->t('Verification Required'),
        ];

        $form['step_2']['description'] = [
          '#markup' => '<div class="verification-description"><p>' .
            $this->t('For security, we need to verify your identity.') . '</p><p>' .
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

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Verify & Continue'),
          '#attributes' => ['class' => ['verify-btn']],
        ];

        $form['actions']['resend'] = [
          '#type' => 'submit',
          '#value' => $this->t('Resend Code'),
          '#submit' => ['::resendVerificationCode'],
          '#attributes' => ['class' => ['resend-btn']],
          '#limit_validation_errors' => [],
        ];
        break;

      case 3:
        // Client info edit form
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($values['appointment_id']);

        $form['client_info_wrapper'] = [
          '#prefix' => '<div class="client-info-wrapper">',
          '#suffix' => '</div>',
        ];

        $form['client_info_wrapper']['rdv_info'] = [
          '#theme' => 'client_info_select',
          '#date' => $appointment->get('start_date')->value,
          '#start_time' => $appointment->get('start_date')->value,
          '#end_time' => $appointment->get('end_date')->value,
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
          '#title' => $this->t('I accept the terms and conditions'),
          '#required' => TRUE,
          '#default_value' => TRUE,
        ];

        $form['actions'] = [
          '#prefix' => '<div class="form-actions">',
          '#suffix' => '</div>',
        ];

        $form['actions']['next'] = [
          '#type' => 'submit',
          '#value' => $this->t('Continue'),
          '#attributes' => ['class' => ['next-btn']],
        ];
        break;

      case 4:
        // Confirmation page
        $appointment = \Drupal::entityTypeManager()
          ->getStorage('appointment')
          ->load($values['appointment_id']);

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
          '#attributes' => ['class' => ['edit-btn']],
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
          '#attributes' => ['class' => ['edit-btn']],
          '#submit' => ['::modifierDate'],
          '#ajax' => [
            'callback' => '::ajaxCallback',
            'wrapper' => 'appointment-edit-form-wrapper',
            'effect' => 'fade',
          ],
        ];

        $form['rdv']['details'] = [
          '#theme' => 'rdv_details',
          '#date' => $appointment->get('start_date')->value,
          '#start_time' => $appointment->get('start_date')->value,
          '#end_time' => $appointment->get('end_date')->value,
        ];

        $form['actions'] = [
          '#prefix' => '<div class="submit-action">',
          '#suffix' => '</div>',
        ];

        $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Save Changes'),
          '#attributes' => ['class' => ['save-btn']],
        ];
        break;

      case 5:
        // Success page
        $form['success'] = [
          '#theme' => 'success_appointment',
          '#message' => $this->t('Your appointment has been updated successfully.'),
        ];
        $this->tempStore->delete('step');
        $this->tempStore->delete('values');
        break;
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $step = $this->tempStore->get('step') ?? 1;
    $values = $this->tempStore->get('values') ?? [];

    switch ($step) {
      case 2:
        // Validate verification code
        $stored_code = $this->tempStore->get('verification_code');
        $entered_code = $form_state->getValue('verification_code');

        if (empty($entered_code)) {
          $form_state->setErrorByName('verification_code', $this->t('Please enter the verification code.'));
        } elseif ($entered_code !== $stored_code) {
          $form_state->setErrorByName('verification_code', $this->t('The verification code is incorrect.'));
        }
        break;

      case 3:
        // Validate client info
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
        // Final submission - update appointment
        try {
          $appointment = \Drupal::entityTypeManager()
            ->getStorage('appointment')
            ->load($values['appointment_id']);

          $appointment->set('customer_first_name', $values['customer_first_name']);
          $appointment->set('customer_last_name', $values['customer_last_name']);
          $appointment->set('customer_email', $values['customer_email']);
          $appointment->set('customer_phone', $values['customer_phone']);
          $appointment->save();

          // Send confirmation email
          \Drupal::service('appointment.email_service')
            ->sendAppointmentConfirmationEmails($appointment->id(), TRUE);

          $this->tempStore->set('step', 5);
        } catch (\Exception $e) {
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
  protected function sendVerificationEmail($email, $code) {
    $params = [
      'subject' => $this->t('Your appointment verification code'),
      'body' => [
        '#theme' => 'verification_email',
        '#code' => $code,
      ],
    ];

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
  public function resendVerificationCode(array &$form, FormStateInterface $form_state) {
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
  public function modifierProfile(array &$form, FormStateInterface $form_state) {
    $this->tempStore->set('step', 3);
    $form_state->setRebuild();
  }

  /**
   * Custom submit handler for modifier date.
   */
  public function modifierDate(array &$form, FormStateInterface $form_state) {
    $this->tempStore->set('step', 3);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback.
   */
  public function ajaxCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('tempstore.private'),
      $container->get('plugin.manager.mail'),
      $container->get('language_manager')
    );
  }
}
