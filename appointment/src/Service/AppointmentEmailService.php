<?php

namespace Drupal\appointment\Service;

use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Datetime\DateFormatterInterface;

/**
 * Service for sending appointment confirmation emails.
 */
class AppointmentEmailService
{
  use StringTranslationTrait;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Constructs a new AppointmentEmailService object.
   *
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(
    MailManagerInterface $mail_manager,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    LoggerChannelFactoryInterface $logger_factory,
    DateFormatterInterface $date_formatter
  ) {
    $this->mailManager = $mail_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->loggerFactory = $logger_factory->get('appointment');
    $this->dateFormatter = $date_formatter;
  }

  /**
   * Sends appointment confirmation emails.
   *
   * @param int $appointment_id
   *   The appointment entity ID.
   *
   * @return bool
   *   TRUE if both emails were sent successfully, FALSE otherwise.
   */
  public function sendAppointmentConfirmationEmails($appointment_id)
  {
    try {
      // Load the appointment entity.
      $appointment = $this->entityTypeManager->getStorage('appointment')->load($appointment_id);
      if (!$appointment) {
        $this->loggerFactory->error('Failed to send appointment emails: Appointment @id not found.', ['@id' => $appointment_id]);
        return FALSE;
      }

      // Load related entities.
      $agency = $this->entityTypeManager->getStorage('agency')->load($appointment->get('agency')->target_id);
      $advisor = $this->entityTypeManager->getStorage('user')->load($appointment->get('adviser')->target_id);
      $type = $this->entityTypeManager->getStorage('taxonomy_term')->load($appointment->get('type')->target_id);

      // Format date and time for email.
      $start_date = new \DateTime($appointment->get('start_date')->value);
      $end_date = new \DateTime($appointment->get('end_date')->value);

      $formatted_date = $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'l j F Y');
      $start_time = $start_date->format('H:i');
      $end_time = $end_date->format('H:i');

      // Get customer information.
      $customer_email = $appointment->get('customer_email')->value;
      $customer_first_name = $appointment->get('customer_first_name')->value;
      $customer_last_name = $appointment->get('customer_last_name')->value;
      $customer_phone = $appointment->get('customer_phone')->value;

      // Send email to customer.
      $customer_result = $this->sendCustomerEmail(
        $customer_email,
        $customer_first_name,
        $customer_last_name,
        $formatted_date,
        $start_time,
        $end_time,
        $agency->label(),
        $agency->get('address')->value,
        $advisor->getAccountName(),
        $type->label()
      );

      // Send email to advisor.
      $advisor_result = $this->sendAdvisorEmail(
        $advisor->getEmail(),
        $advisor->getAccountName(),
        $customer_first_name,
        $customer_last_name,
        $customer_email,
        $customer_phone,
        $formatted_date,
        $start_time,
        $end_time,
        $agency->label(),
        $type->label()
      );

      return $customer_result && $advisor_result;
    } catch (\Exception $e) {
      $this->loggerFactory->error('Error sending appointment emails: @error', ['@error' => $e->getMessage()]);
      return FALSE;
    }
  }

  /**
   * Sends confirmation email to customer.
   *
   * @param string $to
   *   Customer email address.
   * @param string $first_name
   *   Customer first name.
   * @param string $last_name
   *   Customer last name.
   * @param string $date
   *   Formatted appointment date.
   * @param string $start_time
   *   Appointment start time.
   * @param string $end_time
   *   Appointment end time.
   * @param string $agency_name
   *   Agency name.
   * @param string $agency_address
   *   Agency address.
   * @param string $advisor_name
   *   Advisor name.
   * @param string $appointment_type
   *   Appointment type.
   *
   * @return bool
   *   TRUE if email was sent successfully, FALSE otherwise.
   */
  protected function sendCustomerEmail(
    $to,
    $first_name,
    $last_name,
    $date,
    $start_time,
    $end_time,
    $agency_name,
    $agency_address,
    $advisor_name,
    $appointment_type
  ) {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $params = [
      'first_name' => $first_name,
      'last_name' => $last_name,
      'date' => $date,
      'start_time' => $start_time,
      'end_time' => $end_time,
      'agency_name' => $agency_name,
      'agency_address' => $agency_address,
      'advisor_name' => $advisor_name,
      'appointment_type' => $appointment_type,
    ];

    $subject = $this->t('Confirmation of your appointment on @date', ['@date' => $date]);
    $body = [
      '#theme' => 'appointment_customer_email',
      '#params' => $params,
    ];

    return $this->sendEmail($to, $subject, $body, $langcode);
  }

  /**
   * Sends notification email to advisor.
   *
   * @param string $to
   *   Advisor email address.
   * @param string $advisor_name
   *   Advisor name.
   * @param string $customer_first_name
   *   Customer first name.
   * @param string $customer_last_name
   *   Customer last name.
   * @param string $customer_email
   *   Customer email.
   * @param string $customer_phone
   *   Customer phone.
   * @param string $date
   *   Formatted appointment date.
   * @param string $start_time
   *   Appointment start time.
   * @param string $end_time
   *   Appointment end time.
   * @param string $agency_name
   *   Agency name.
   * @param string $appointment_type
   *   Appointment type.
   *
   * @return bool
   *   TRUE if email was sent successfully, FALSE otherwise.
   */
  protected function sendAdvisorEmail(
    $to,
    $advisor_name,
    $customer_first_name,
    $customer_last_name,
    $customer_email,
    $customer_phone,
    $date,
    $start_time,
    $end_time,
    $agency_name,
    $appointment_type
  ): bool {
    $langcode = $this->languageManager->getDefaultLanguage()->getId();
    $params = [
      'advisor_name' => $advisor_name,
      'customer_first_name' => $customer_first_name,
      'customer_last_name' => $customer_last_name,
      'customer_email' => $customer_email,
      'customer_phone' => $customer_phone,
      'date' => $date,
      'start_time' => $start_time,
      'end_time' => $end_time,
      'agency_name' => $agency_name,
      'appointment_type' => $appointment_type,
    ];

    $subject = $this->t('New appointment scheduled on @date', ['@date' => $date]);
    $body = [
      '#theme' => 'appointment_advisor_email',
      '#params' => $params,
    ];

    return $this->sendEmail($to, $subject, $body, $langcode);
  }

  /**
   * Helper function to send an email.
   *
   * @param string $to
   *   Email recipient.
   * @param string $subject
   *   Email subject.
   * @param array $body
   *   Renderable array for the email body.
   * @param string $langcode
   *   Language code.
   *
   * @return bool
   *   TRUE if email was sent successfully, FALSE otherwise.
   */
  protected function sendEmail($to, $subject, array $body, $langcode): bool
  {
    $params = [
      'subject' => $subject,
      'body' => $body,
    ];

    // Debug before sending
    \Drupal::logger('appointment')->notice('Attempting to send email to: @to', ['@to' => $to]);
    \Drupal::logger('appointment')->debug('Email params: @params', ['@params' => print_r($params, TRUE)]);

    try {
      $result = $this->mailManager->mail(
        'appointment', // Must match your module name
        'appointment_confirmation', // Must match hook_mail key
        $to,
        $langcode,
        $params,
        NULL, // Reply-to (optional)
        TRUE // Send parameter (CRUCIAL!)
      );

      // Debug after sending
      \Drupal::logger('appointment')->notice('Mail result: @result', ['@result' => print_r($result, TRUE)]);

      if ($result['result'] !== TRUE) {
        \Drupal::logger('appointment')->error('Failed to send email to @to', ['@to' => $to]);
        return FALSE;
      }

      return TRUE;
    } catch (\Exception $e) {
      \Drupal::logger('appointment')->error('Email send exception: @error', [
        '@error' => $e->getMessage(),
      ]);
      return FALSE;
    }
  }
}
