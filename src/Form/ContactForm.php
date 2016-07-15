<?php

namespace Drupal\constant_contact\Form;

use Ctct\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\EmailAddress;
use Drupal\constant_contact\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\constant_contact\ConstantContactManager;

/**
 * Account information.
 */
class ContactForm extends FormBase {

  /** @var \Drupal\constant_contact\ConstantContactManagerInterface */
  protected $constantContactManager;

  /** @var  \Drupal\constant_contact\AccountInterface $account */
  protected $account;

  /** @var  int $id */
  protected $id;

  /**
   * AccountInfoForm constructor.
   *
   * @param \Drupal\constant_contact\ConstantContactManagerInterface $constant_contact_manager
   */
  public function __construct(ConstantContactManagerInterface $constant_contact_manager) {
    $this->constantContactManager = $constant_contact_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('constant_contact.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constant_contact_contact';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $constant_contact_account = NULL, $id = NULL) {
    $this->account = $constant_contact_account;
    $this->id = $id;

    $account_info = $this->constantContactManager->getAccountInfo($constant_contact_account);
    if (!$account_info instanceof AccountInfo) {
      return $form['message'] = [
        '#type' => 'markup',
        '#markup' => t('Account not found.')
      ];
    }
    $contact = NULL;
    if ($id) {
      $contact = $this->constantContactManager->getContact($constant_contact_account, $id);
    }

    $form['first_name'] = [
      '#type' => 'textfield',
      '#default_value' => !empty($contact) ? $contact->first_name : '',
      '#title' => $this->t('First name'),
    ];

    $form['last_name'] = [
      '#type' => 'textfield',
      '#default_value' => !empty($contact) ? $contact->last_name : '',
      '#title' => $this->t('Last name'),
    ];

    // @todo:
    // A user with status = 'OPTOUT' can't be added by the account owner.
    $status = $this->constantContactManager->getContactStatuses();
    $form['status'] = [
      '#type' => 'select',
      '#options' => array_combine($status, $status),
      '#default_value' => !empty($contact) ? $contact->status : '',
      '#title' => $this->t('Status'),
    ];

    $form['confirmed'] = [
      '#type' => 'select',
      '#options' => ['N', 'Y'],
      '#default_value' => !empty($contact) ? $contact->confirmed : '',
      '#title' => $this->t('Confirmed'),
    ];

    /*
     * Currently only one email address is supported for each contact. If the
     * account uses the new contact management system, it is possible to create
     * more than 1 email address per contact using the product GUI.
     * The API ignores additional email addresses.
     */
    $form['email_address'] = [
      '#type' => 'textfield',
      '#default_value' => !empty($contact) ? $contact->email_addresses[0]->email_address : '',
      '#title' => $this->t('Email address'),
    ];

    $form['lists'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Mailing lists'),
      '#options' => $this->constantContactManager->getContactListsOptions($constant_contact_account, FALSE),
      '#default_value' => !empty($contact) ? $this->constantContactManager->getListIdsForContact($contact->lists) : '',
    ];

    // disabled in edit mode.
    $created_date = !empty($contact) ? strtotime($contact->created_date) : REQUEST_TIME;
    $form['created_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Created date'),
      '#default_value' => DrupalDateTime::createFromTimestamp($created_date),
      '#size' => 20,
      '#disabled' => !empty($contact),
    );

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save contact'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => [[$this, 'standardCancel']],
      '#validate' => [],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $contact = ($this->id) ?
      $this->constantContactManager->getContact($this->account, $this->id) :
      new Contact();

    $now = date(DATE_ISO8601, REQUEST_TIME);
    // new contact.
    if (!$this->id) {
      $contact->created_date = $now;
    }

    $values = $form_state->getValues();

    $contact->modified_date = $now;
    $contact->first_name = trim($values['first_name']);
    $contact->last_name = trim($values['last_name']);
    $contact->confirmed = (bool) $values['confirmed'];
    $contact->status = trim($values['status']);

    foreach (array_keys(array_filter($values['lists'])) as $list) {
      $contact->addList((string) $list);
    }

    $emailAddress = new \stdClass();
    $emailAddress->email_address = trim($values['email_address']);
    $contact->email_addresses = [$emailAddress];

    /*
     * The third parameter of addContact defaults to false, but if this were set to true it would tell Constant
     * Contact that this action is being performed by the contact themselves, and gives the ability to
     * opt contacts back in and trigger Welcome/Change-of-interest emails.
     *
     * See: http://developer.constantcontact.com/docs/contacts-api/contacts-index.html#opt_in
     */

    $isCurrentUser = \Drupal::currentUser()->getEmail() == trim($values['email_address']);

    if($this->id) {
      $returnContact = $this->constantContactManager->putContact($this->account, $contact, $isCurrentUser);
    }
    else {
      $returnContact = $this->constantContactManager->createContact($this->account, $contact, $isCurrentUser);
    }

    if ($returnContact instanceof Contact) {
      $name = $returnContact->first_name . ' ' . $returnContact->last_name;
      if($this->id) {
        $this->logger('constant_contact')->info('Contact: %label updated by %user', [
          '%label' => $name,
          '%user' => \Drupal::currentUser()->getAccountName(),
        ]);
        $message = $this->t('Updated contact: %label.', ['%label' => $name]);
      }
      else {
        $this->logger('constant_contact')->info('Contact: %label created by %user', [
          '%label' => $name,
          '%user' => \Drupal::currentUser()->getAccountName(),
        ]);
        $message = $this->t('Created contact: %label.', ['%label' => $name]);
      }

      // Cache is stale.
      \Drupal::cache(ConstantContactManager::CC_CACHE_BIN)->delete('constant_contact:contacts:' . $this->account->getApiKey());
    }
    else {
      $message = $this->t('Contact operation failed.');
    }
    drupal_set_message($message);

    $form_state->setRedirect('constant_contact.contacts.collection', ['constant_contact_account' => $this->account->id()]);
  }

  /**
   * Submit handler for cancel button
   */
  public function standardCancel($form, FormStateInterface $form_state) {
    $form_state->setRedirect('constant_contact.contact_list.collection', ['constant_contact_account' => $this->account->id()]);
  }

  /**
   * @return array
   */
  public function getEmptyOption() {
    return [0 => '- Select -'];
  }

}
