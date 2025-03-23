<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a listing of Appointment entities.
 */
class AppointmentListBuilder extends EntityListBuilder implements FormInterface {

  protected $renderer;
  protected $dateFormatter;
  protected $filterValues = [
    'title' => '',
    'agency' => '',
    'type' => '',
    'adviser' => '',
  ];

  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RendererInterface $renderer, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);
    $this->renderer = $renderer;
    $this->dateFormatter = $date_formatter;
  }

  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('renderer'),
      $container->get('date.formatter')
    );
  }

  public function buildHeader(): array {
    $header['title'] = $this->t('Title');
    $header['start_date'] = $this->t('Start Date');
    $header['end_date'] = $this->t('End Date');
    $header['agency'] = $this->t('Agency');
    $header['adviser'] = $this->t('Adviser');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  public function buildRow(EntityInterface $entity): array {
    $row['title'] = $entity->get('title')->value;
    $row['start_date'] = $this->dateFormatter->format(strtotime($entity->get('start_date')->value), 'custom', 'd-m-Y H:i');
    $row['end_date'] = $this->dateFormatter->format(strtotime($entity->get('end_date')->value), 'custom', 'd-m-Y H:i');
    $row['agency'] = ['data' => $entity->get('agency')->entity ? $entity->get('agency')->entity->toLink()->toString() : ''];
    $row['adviser'] = ['data' => $entity->get('adviser')->entity ? $entity->get('adviser')->entity->toLink()->toString() : ''];
    $row['status'] = $entity->get('status')->value;
    return $row + parent::buildRow($entity);
  }

  public function render(): array {
    $build['filter_form'] = \Drupal::formBuilder()->getForm($this);
    $build['table'] = parent::render();
    return $build;
  }

  public function getFormId(): string {
    return 'appointment_filter_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = \Drupal::request();
    $this->filterValues = [
      'title' => $request->query->get('title', ''),
      'agency' => $request->query->get('agency', ''),
      'type' => $request->query->get('type', ''),
      'adviser' => $request->query->get('adviser', ''),
    ];

    $form['add_appointment'] = [
      '#type' => 'link',
      '#title' => $this->t('+ Add Appointment'),
      '#url' => \Drupal\Core\Url::fromUri('internal:/appointment/multi-step-form'),
      '#attributes' => ['class' => ['button', 'button--primary']],
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

    $form['filters']['actions']['reset'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset'),
      '#submit' => ['::resetFilters'],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {}

  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $this->filterValues = [
      'title' => $form_state->getValue('title'),
      'agency' => $form_state->getValue('agency'),
      'type' => $form_state->getValue('type'),
      'adviser' => $form_state->getValue('adviser'),
    ];

    $form_state->setRedirect('<current>', [], [
      'query' => [
        'title' => $this->filterValues['title'],
        'agency' => $this->filterValues['agency'],
        'type' => $this->filterValues['type'],
        'adviser' => $this->filterValues['adviser'],
      ],
    ]);
  }

  public function resetFilters(array &$form, FormStateInterface $form_state): void {
    $form_state->setRedirect('<current>');
  }

  protected function getEntityIds(): array {
    $query = $this->getStorage()->getQuery()
      ->accessCheck(TRUE)
      ->sort($this->entityType->getKey('label'));

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
      $query->condition('adviser.entity.name', $this->filterValues['adviser'], 'CONTAINS');
    }

    $query->pager(50);

    return $query->execute();
  }
}
