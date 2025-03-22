<?php

namespace Drupal\appointment\Controller;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
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
   * The filter input value.
   *
   * @var string
   */
  protected $filterValue = '';

  /**
   * Constructs a new AppointmentListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, RendererInterface $renderer)
  {
    parent::__construct($entity_type, $storage);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type)
  {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('renderer')
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
    $row['title'] = $entity->label();
    $row['start_date'] = $entity->get('start_date')->value;
    $row['end_date'] = $entity->get('end_date')->value;
    $row['agency'] = $entity->get('agency')->entity ? $entity->get('agency')->entity->label() : '';
    $row['adviser'] = $entity->get('adviser')->entity ? $entity->get('adviser')->entity->label() : '';
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
    $this->filterValue = $request->query->get('filter', '');
    $form['add_appointment'] = [
      '#type' => 'link',
      '#title' => $this->t('+ Add Appointment'),
      '#url' => \Drupal\Core\Url::fromUri('internal:/admin/structure/appointment/add'),
      '#attributes' => [
        'class' => ['button', 'button--primary'],
      ],
    ];
    $form['filter'] = [
      '#type' => 'search',
      '#title' => $this->t('Filter by Title'),
      '#default_value' => $this->filterValue,
      '#size' => 30,
      '#placeholder' => $this->t('Enter title...'),
    ];

    $form['actions'] = [
      '#type' => 'actions',
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
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
    // Set the filter value from the form input.
    $this->filterValue = $form_state->getValue('filter');

    // Redirect to the same page with the filter value in the query parameters.
    $form_state->setRedirect('<current>', [], [
      'query' => [
        'filter' => $this->filterValue,
      ],
    ]);
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

    if (!empty($this->filterValue)) {
      $query->condition('title', $this->filterValue, 'CONTAINS');
    }

    $query->pager(50);

    return $query->execute();
  }
}
