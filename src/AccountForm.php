<?php

namespace Drupal\constant_contact;

use Ctct\Components\Account\AccountInfo;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for profile type forms.
 */
class AccountForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\constant_contact\Entity\Account $entity */
    $entity = $this->entity;

    if ($this->operation == 'add') {
      $form['#title'] = $this->t('Add account');
    }
    else {
      $form['#title'] = $this->t('Edit %label account', ['%label' => $entity->label()]);
    }

    $form['api_key'] = [
      '#title' => t('API key'),
      '#type' => 'textfield',
      '#default_value' => $entity->id(),
      '#required' => TRUE,
    ];
    $form['application'] = [
      '#title' => t('Application'),
      '#type' => 'textfield',
      '#default_value' => $entity->label(),
    ];
    $form['secret'] = [
      '#title' => t('Secret'),
      '#type' => 'textfield',
      '#default_value' => $entity->getSecret(),
    ];
    $form['access_token'] = [
      '#title' => t('Access token'),
      '#type' => 'textfield',
      '#default_value' => $entity->getAccessToken(),
      '#required' => TRUE,
    ];

    return parent::form($form, $form_state, $entity);
  }

  /**
   * @inheritdoc
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    // Make sure the API key and access token are valid.
    $values = $form_state->getValues();
    $result = \Drupal::service('constant_contact.manager')->getAccountInfoFromData($values['api_key'], $values['access_token']);
    if (!$result instanceof  AccountInfo) {
      $form_state->setErrorByName('api_key', $this->t('Make sure the API key is valid.'));
      $form_state->setErrorByName('access_token', $this->t('Make sure the Access token is valid.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    /** @var \Drupal\constant_contact\Entity\Account $entity */
    $entity = $this->entity;
    $status = $entity->save();

    if ($status == SAVED_UPDATED) {
      drupal_set_message(t('%label account has been updated.', ['%label' => $entity->label()]));
    }
    else {
      drupal_set_message(t('%label account has been created.', ['%label' => $entity->label()]));
    }
    $form_state->setRedirectUrl($this->entity->urlInfo('collection'));
  }

  /**
   * {@inheritdoc}
   */
  public function delete(array $form, FormStateInterface $form_state) {
    $form_state->setRedirect('entity.constant_contact_account.collection');
  }

}
