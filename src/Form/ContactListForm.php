<?php

namespace Drupal\constant_contact\Form;

use Ctct\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\constant_contact\ConstantContactManager;

/**
 * Account information.
 */
class ContactListForm extends FormBase {

  /** @var  \Drupal\constant_contact\Entity\Account $account */
  protected $account;

  protected $accountInfo;

  /** @var \Drupal\constant_contact\ConstantContactManagerInterface */
  protected $constantContactManager;

  protected $fields;

  protected $listid;

  /**
   * AccountInfoForm constructor.
   *
   * @param \Drupal\constant_contact\ConstantContactManagerInterface $constant_contact_manager
   */
  public function __construct(ConstantContactManagerInterface $constant_contact_manager) {
    $this->account = \Drupal::request()->get('constant_contact_account');
    $this->listid = \Drupal::request()->get('listid');
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
    return 'constant_contact_contact_list';
  }

  protected function getFields() {
    return $this->fields;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $account_info = $this->constantContactManager->getAccountInfo($this->account);
    if (!$account_info instanceof AccountInfo) {
      return $form['message'] = [
        '#type' => 'markup',
        '#markup' => t('Account not found.')
      ];
    }
    $list = NULL;
    if ($this->listid) {
      $list = $this->constantContactManager->getContactList($this->account, $this->listid);

    }

    $form['name'] = [
      '#type' => 'textfield',
      '#default_value' => !empty($list) ? $list->name : '',
      '#title' => $this->t('Name'),
    ];

    $status = ['ACTIVE', 'HIDDEN'];
    $form['status'] = [
      '#type' => 'select',
      '#options' => array_combine($status, $status),
      '#default_value' => !empty($list) ? $list->status : '',
      '#title' => $this->t('Status'),
    ];

    // disabled in edit mode.
    $created_date = !empty($list) ? strtotime($list->created_date) : REQUEST_TIME;
    $form['created_date'] = array(
      '#type' => 'datetime',
      '#title' => $this->t('Created date'),
      '#default_value' => DrupalDateTime::createFromTimestamp($created_date),
      '#size' => 20,
      '#disabled' => !empty($list),
    );

    $form['listid'] = [
      '#type' => 'value',
      '#value' => !empty($list) ? $list->id : 0,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save account'),
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
    $listid = $form_state->getValue('listid');
    $list = ($listid) ?
      $this->constantContactManager->getContactList($this->account, $listid) :
      new ContactList();

    $now = date(DATE_ISO8601, REQUEST_TIME);
    // old list.
    if ($listid) {
       $list->id = $listid;
    }
    else {
      $list->created_date = $now;
    }

    $list->name = $form_state->getValue('name');
    $list->status = $form_state->getValue('status');
    $list->modified_date = $now;

    if($listid) {
      $returnList = $this->constantContactManager->putContactList($this->account, $list);
    }
    else {
      $returnList = $this->constantContactManager->createContactList($this->account, $list);
    }

    if ($returnList instanceof ContactList) {
      if($listid) {
        $this->logger('constant_contact')->info('Contact list: %label created by %user', [
          '%label' => $returnList->name,
          '%user' => \Drupal::currentUser()->getAccountName(),
        ]);
        $message = $this->t('Created contact list: %label.', ['%label' => $returnList->name]);
      }
      else {
        $this->logger('constant_contact')->info('Contact list: %label updated by %user', [
          '%label' => $returnList->name,
          '%user' => \Drupal::currentUser()->getAccountName(),
        ]);
        $message = $this->t('Updated contact list %label.', ['%label' => $returnList->name]);
      }

      // Cache is stale.
      \Drupal::cache(ConstantContactManager::CC_CACHE_BIN)->delete('constant_contact:contact_lists:' . $this->account->id());
    }
    else {
      $message = $this->t('Contact list operation failed.');
    }
    drupal_set_message($message);

    $form_state->setRedirect('constant_contact.contact_list.collection', ['constant_contact_account' => $this->account->id()]);

  }

  /**
   * Submit handler for cancel button
   */
  public function standardCancel($form, FormStateInterface $form_state) {
    $form_state->setRedirect('constant_contact.contact_list.collection', ['constant_contact_account' => $this->account->id()]);
  }

}

