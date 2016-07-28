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
 * @deprecated ???
 *
 * Form for deleting an image effect.
 */
class ContactListDeleteForm extends ConfirmFormBase {

  /**
   *
   *
   * @var \Drupal\constant_contact\AccountInterface
   */
  protected $account;

  /**
   * The list to be deleted.
   *
   * @var \Ctct\Components\Contacts\ContactList
   */
  protected $list;

  /**
   * {@inheritdoc}
   */
  public function getQuestion() {
    return $this->t('Are you sure you want to delete the @list list?', array('@list' => $this->listId));
  }

  /**
   * {@inheritdoc}
   */
  public function getConfirmText() {
    return $this->t('Delete');
  }

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return new Url('constant_contact.contact_list.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'contact_list_delete_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $listid = NULL) {
    $this->list = \Drupal::service('constant_contact.manager')->getContactList($listid);

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $ccAccount = \Drupal::service('constant_contact.manager')->getCCAccount();
    \Drupal::service('constant_contact.manager')->deleteContactList($this->list->id);
    \Drupal::cache(ConstantContactManager::CC_CACHE_BIN)->delete('constant_contact:contact_lists:' . $ccAccount->id());

    $this->logger('constant_contact')->info('Contact list: %label deleted by %user', [
      '%label' => $this->list->name,
      '%user' => \Drupal::currentUser()->getAccountName(),
    ]);
    drupal_set_message($this->t('The Contact list %name has been deleted.', array('%name' => $this->list->name)));

    $form_state->setRedirect('constant_contact.contact_list.collection');
  }

}
