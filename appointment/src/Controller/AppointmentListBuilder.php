<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DateFormatterInterface; // Add this line.
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Appointment entities.
 */
class AppointmentListBuilder extends EntityListBuilder implements FormInterface
{

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The date formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter; // Add this property.

  /**
   * The filter values.
   *
   * @var array
   */
  protected $filterValues = [
    'title' => '',
    'agency' => '',
    'type' => '',
    'adviser' => '', // New filter for adviser name.
  ];

  /**
   * Constructs a new AppointmentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RendererInterface $renderer, DateFormatterInterface $date_formatter) // Add this parameter.
  {
    parent::__construct($entity_type, $storage);
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter; // Initialize the date formatter.
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
  {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('renderer'),
      $container->get('date.formatter') // Add this service.
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array
  {
    $header['title'] = $this->t('Title');
    $header['start_date'] = $this->t('Start Date');
    $header['end_date'] = $this->t('End Date');
    $header['agency'] = $this->t('Agency');
    $header['adviser'] = $this->t('Adviser');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array
  {
    $row['title'] = $entity->get('title')->value;

    // Format the start date.
    $start_date = $entity->get('start_date')->value;
    $formatted_start_date = $this->dateFormatter->format(strtotime($start_date), 'custom', 'd-m-Y H:i');
    $row['start_date'] = $formatted_start_date;

    // Format the end date.
    $end_date = $entity->get('end_date')->value;
    $formatted_end_date = $this->dateFormatter->format(strtotime($end_date), 'custom', 'd-m-Y H:i');
    $row['end_date'] = $formatted_end_date;

    $agency = $entity->get('agency')->entity;
    $agency_name = $agency ? $agency->label() : '';
    $agency_link = $agency ? $agency->toLink($agency_name)->toString() : '';
    $row['agency'] = ['data' => $agency_link];

    $adviser = $entity->get('adviser')->entity;
    $adviser_name = $adviser ? $adviser->getDisplayName() : '';
    $adviser_link = $adviser ? $adviser->toLink($adviser_name)->toString() : '';
    $row['adviser'] = ['data' => $adviser_link];

    $row['status'] = $entity->get('status')->value;
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array
  {
    // Build the filter form.
    $build['filter_form'] = \Drupal::formBuilder()->getForm($this);

    // Build the entity list.
    $build['table'] = parent::render();

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'appointment_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $request = \Drupal::request();
    $this->filterValues = [
      'title' => $request->query->get('title', ''),
      'agency' => $request->query->get('agency', ''),
      'type' => $request->query->get('type', ''),
      'adviser' => $request->query->get('adviser', ''), // New filter for adviser name.
    ];

    $form['add_appointment'] = [
      '#type' => 'link',
      '#title' => $this->t('+ Add Appointment'),
      '#url' => \Drupal\Core\Url::fromUri('internal:/appointment/multi-step-form'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];

    $form['filters'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form--inline']],
    ];

    $form['filters']['title'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by Title'),
      '#default_value' => $this->filterValues['title'],
      '#size' => 30,
      '#placeholder' => $this->t('Enter title...'),
    ];

    $form['filters']['adviser'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by Adviser'),
      '#default_value' => $this->filterValues['adviser'],
      '#size' => 30,
      '#placeholder' => $this->t('Enter adviser name...'),
    ];

    // Load agencies for the agency filter.
    $agencies = \Drupal::entityTypeManager()->getStorage('agency')->loadMultiple();
    $agency_options = ['' => $this->t('- Any -')];
    foreach ($agencies as $agency) {
      $agency_options[$agency->id()] = $agency->label();
    }

    $form['filters']['agency'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by Agency'),
      '#empty_option' => $this->t('All agencies...'),
      '#options' => $agency_options,
      '#default_value' => $this->filterValues['agency'],
    ];

    // Load appointment types for the type filter.
    $types = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->loadByProperties(['vid' => 'appointment_type']);
    $type_options = ['' => $this->t('- Any -')];
    foreach ($types as $type) {
      $type_options[$type->id()] = $type->label();
    }

    $form['filters']['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Filter by Type'),
      '#options' => $type_options,
      '#empty_option' => $this->t('All types...'),
      '#default_value' => $this->filterValues['type'],
    ];

    $form['filters']['actions'] = [
      '#type' => 'actions',
    ];

    $form['filters']['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];

    // Add reset button.
    $form['filters']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetFilters'], // Custom submit handler for reset.
      '#limit_validation_errors' => [], // Skip validation for reset.
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    // No validation needed for this simple filter.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    // Set the filter values from the form input.
    $this->filterValues = [
      'title' => $form_state->getValue('title'),
      'agency' => $form_state->getValue('agency'),
      'type' => $form_state->getValue('type'),
      'adviser' => $form_state->getValue('adviser'), // New filter for adviser name.
    ];

    // Redirect to the same page with the filter values in the query parameters.
    $form_state->setRedirect('<current>', [], [
      'query' => [
        'title' => $this->filterValues['title'],
        'agency' => $this->filterValues['agency'],
        'type' => $this->filterValues['type'],
        'adviser' => $this->filterValues['adviser'], // New filter for adviser name.
      ],
    ]);
  }

  /**
   * Custom submit handler for the reset button.
   */
  public function resetFilters(array &$form, FormStateInterface $form_state)
  {
    // Redirect to the same page without any query parameters to reset filters.
    $form_state->setRedirect('<current>');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds(): array
  {
    // Get the base query.
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('label'));

    // Apply filters.
    if (!empty($this->filterValues['title'])) {
      $query->condition('title', $this->filterValues['title'], 'CONTAINS');
    }
    if (!empty($this->filterValues['agency'])) {
      $query->condition('agency', $this->filterValues['agency']);
    }
    if (!empty($this->filterValues['type'])) {
      $query->condition('type', $this->filterValues['type']);
    }
    if (!empty($this->filterValues['adviser'])) {
      // Filter by adviser's display name.
      $query->condition('adviser.entity.name', $this->filterValues['adviser'], 'CONTAINS');
    }

    $query->pager(50);

    return $query->execute();
  }
}
