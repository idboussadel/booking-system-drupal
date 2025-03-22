<?php

namespace Drupal\appointment\Entity;

use Drupal\Core\Entity\Annotation\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the Agency entity.
 *
 * @ContentEntityType(
 *   id = "agency",
 *   label = @Translation("Agency"),
 *   handlers = {
 *     "list_builder" = "Drupal\appointment\Controller\AgencyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *     "default" = "Drupal\appointment\Form\Agency\AgencyAddForm",
 *       "add" = "Drupal\appointment\Form\Agency\AgencyAddForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     }
 *   },
 *   base_table = "agency",
 *   admin_permission = "administer agency entity",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "address" = "address",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/agency/{agency}",
 *     "add-form" = "/admin/structure/agency/add",
 *     "edit-form" = "/agency/structure/agency/{agency}/edit",
 *     "delete-form" = "/agency/{agency}/delete",
 *     "collection" = "/admin/structure/agencies"
 *   },
 *   field_ui_base_route = "entity.agency.settings"
 * )
 */
class Agency extends ContentEntityBase
{
  public static function baseFieldDefinitions(EntityTypeInterface $entityType): array
  {
    $fields = parent::baseFieldDefinitions($entityType);
    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['address'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Address'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['contact_info'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Contact Information'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['field_working_hours'] = BaseFieldDefinition::create('office_hours')
      ->setLabel(t('Working Hours'))
      ->setDescription(t('The working hours of the agency.'))
      ->setCardinality(7)
      ->setSettings([
        'time_format' => 'H:i',
        'comment' => FALSE,
        'valhrs' => FALSE,
        'cardinality_per_day' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'office_hours_default',
        'weight' => 10,
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'office_hours',
        'weight' => 10,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    return $fields;
  }
}
