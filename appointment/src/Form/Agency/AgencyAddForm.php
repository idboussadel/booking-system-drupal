<?php

namespace Drupal\appointment\Form\Agency;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the agency add form.
 */
class AgencyAddForm extends ContentEntityForm
{

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'agency_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form = parent::buildForm($form, $form_state);

    return $form;
  }

  /**
   * {@inheritdoc}
   * @throws EntityStorageException
   */
  public function save(array $form, FormStateInterface $form_state): void
  {
    $this->entity->save();

    $this->messenger()->addMessage($this->t('The agency %name has been saved.', [
      '%name' => $this->entity->label(),
    ]));

    $form_state->setRedirectUrl(Url::fromRoute('entity.agency.collection'));
  }
}
