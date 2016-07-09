<?php

namespace Drupal\constant_contact\Form;

use Drupal\Core\Form\ConfirmFormBase;
use Drupal\Core\Form\FormStateInterface;
use Ctct\ConstantContact;
use CtCt\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Drupal\constant_contact\AccountInterface;
use Drupal\Core\Url;
use Drupal\constant_contact\ConstantContactManager;

/**
 * Form for deleting an image effect.
 */
class ContactRemoveForm extends ConfirmFormBase {

  /**
   *
   *
   * @var \Drupal\constant_contact\AccountInterface
   */
  protected $account;


  protected $contact;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to remove @user from all lists?', ['@user' => $this->contact->email_addresses[0]->email_address]);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Remove');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('constant_contact.contacts.collection', ['constant_contact_account' => $this->account->id()]);
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_remove_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $constant_contact_account = NULL, $id = NULL) {
    $this->account = $constant_contact_account;
    $this->contact = \Drupal::service('constant_contact.manager')->getContact($constant_contact_account, $id);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Empty the array of lists to remove the user from all lists.
    $this->contact->lists = [];
    \Drupal::service('constant_contact.manager')->putContact($this->account, $this->contact);

    \Drupal::cache(ConstantContactManager::CC_CACHE_BIN)->delete('constant_contact:contacts:' . $this->account->getApiKey());

    $this->logger('constant_contact')->info('Contact: %label removed from all lists by %user', [
      '%label' => $this->contact->email_addresses[0]->email_address,
      '%user' => \Drupal::currentUser()->getAccountName(),
    ]);
    drupal_set_message($this->t('Contact: %name has been removed from all lists.', ['%name' => $this->contact->email_addresses[0]->email_address]));

    $form_state->setRedirect('constant_contact.contacts.collection', ['constant_contact_account' => $this->account->id()]);
  }

}
