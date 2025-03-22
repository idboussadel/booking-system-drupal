<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Appointment entity.
 *
 * @ContentEntityType(
 *   id = "appointment",
 *   label = @Translation("Appointment"),
 *   handlers = {
 *     "list_builder" = "Drupal\appointment\Controller\AppointmentListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "default" = "Drupal\appointment\Form\Appointment\AppointmentAddForm",
 *       "add" = "Drupal\appointment\Form\Appointment\AppointmentAddForm",
 *       "edit" = "Drupal\appointment\Form\Appointment\AppointmentEditForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "appointment",
 *   admin_permission = "administer appointment entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "title",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/appointment/{appointment}",
 *     "add-form" = "/admin/structure/appointment/add",
 *     "edit-form" = "/appointment/structure/appointment/{appointment}/edit",
 *     "delete-form" = "/appointment/{appointment}/delete",
 *     "collection" = "/admin/structure/appointments"
 *   },
 *   field_ui_base_route = "entity.appointment.settings"
 * )
 */
class Appointment extends ContentEntityBase
{
  public static function BaseFieldDefinition(EntityTypeInterface $entity_type): array
  {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Title'))
      ->setDescription(t('The title of the appointment'))
      ->setRequired(true)
      ->setDefaultValue('test')
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['start_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Start date and time'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['end_date'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('End date and time'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['agency'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('AgencyAdd'))
      ->setSetting('target_type', 'agency')
      ->setDescription(t('The agency'))
      ->setRequired(TRUE)
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

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
      ->setDisplayConfigurable('form', true)
      ->setDisplayConfigurable('view', true);

    $fields['adviser'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Adviser'))
      ->setSetting('target_type', 'user')
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Status'))
      ->setSetting('allowed_values', [
        'pending' => t('Pending'),
        'confirmed' => t('Confirmed'),
        'cancelled' => t('Cancelled'),
      ])
      ->setRequired(TRUE);

    $fields['notes'] = BaseFieldDefinition::create('text_long')
      ->setLabel(t('Notes'));

    // Customer information fields.
    $fields['customer_first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Prenom'))
      ->setRequired(TRUE);
    $fields['customer_last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Nom'))
      ->setRequired(TRUE);
    $fields['customer_email'] = BaseFieldDefinition::create('email')
      ->setLabel(t('Customer Email'))
      ->setRequired(TRUE);
    $fields['customer_phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer Phone'))
      ->setRequired(TRUE);

    return $fields;
  }
}
