<?php

declare(strict_types=1);

namespace Drupal\appointment\Entity;

use Drupal\appointment\AppointmentInterface;
use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the appointment entity class.
 *
 * @ContentEntityType(
 *   id = "appointment",
 *   label = @Translation("Appointment"),
 *   label_collection = @Translation("Appointments"),
 *   label_singular = @Translation("appointment"),
 *   label_plural = @Translation("appointments"),
 *   label_count = @PluralTranslation(
 *     singular = "@count appointments",
 *     plural = "@count appointments",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\appointment\Controller\AppointmentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\appointment\Form\Appointment\AppointmentAddForm",
 *       "edit" = "Drupal\appointment\Form\appointment\AppointmentEditForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *        "default" = "Drupal\appointment\Form\Appointment\AppointmentAddForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "appointment",
 *   admin_permission = "administer appointment",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *      "canonical" = "/appointment/{appointment}",
 *      "add-form" = "/admin/structure/appointment/add",
 *      "edit-form" = "/appointment/{appointment}/edit",
 *      "delete-form" = "/appointment/{appointment}/delete",
 *      "collection" = "/admin/structure/appointments"
 *    },
 *   field_ui_base_route = "entity.appointment.settings",
 * )
 */
final class Appointment extends ContentEntityBase implements AppointmentInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    // Title field.
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the appointment'))
      ->setRequired(TRUE)
      ->setDefaultValue('test')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Start date field.
    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date and time'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // End date field.
    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date and time'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Agency field.
    $fields['agency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Agency'))
      ->setSetting('target_type', 'agency')
      ->setDescription(t('The agency'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Appointment type field.
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Appointment Type'))
      ->setDescription(t('The type of appointment.'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler', 'default')
      ->setSetting('handler_settings', [
        'target_bundles' => [
          'appointment_type' => 'appointment_type',
        ],
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Adviser field.
    $fields['adviser'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Adviser'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Status field.
    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSetting('allowed_values', [
        'pending' => t('Pending'),
        'confirmed' => t('Confirmed'),
        'cancelled' => t('Cancelled'),
      ])
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Notes field.
    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'))
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    // Customer information fields.
    $fields['customer_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Prenom'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['customer_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nom'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['customer_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Customer Email'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['customer_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Phone'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }

}
