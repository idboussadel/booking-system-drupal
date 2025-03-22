<?php

declare(strict_types=1);

namespace Drupal\appointment\Entity;

use Drupal\appointment\AgencyInterface;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines the agency entity class.
 *
 * @ContentEntityType(
 *   id = "agency",
 *   label = @Translation("Agency"),
 *   label_collection = @Translation("Agencies"),
 *   label_singular = @Translation("agency"),
 *   label_plural = @Translation("agencies"),
 *   label_count = @PluralTranslation(
 *     singular = "@count agencies",
 *     plural = "@count agencies",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\appointment\Controller\AgencyListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\appointment\Form\Agency\AgencyAddForm",
 *       "edit" = "Drupal\appointment\Form\AgencyForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "agency",
 *   admin_permission = "administer agency",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *    "canonical" = "/agency/{agency}",
 *      "add-form" = "/admin/structure/agency/add",
 *      "edit-form" = "/agency/structure/agency/{agency}/edit",
 *      "delete-form" = "/agency/{agency}/delete",
 *      "collection" = "/admin/structure/agencies"
 *   },
 *   field_ui_base_route = "entity.agency.settings",
 * )
 */
final class Agency extends ContentEntityBase implements AgencyInterface {

  /**
   * {@inheritdoc}
   */

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);
    // Name field.
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

    // Address field.
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

    // Contact Information field.
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

    // Working Hours field.
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
