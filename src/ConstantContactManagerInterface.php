<?php


namespace Drupal\constant_contact;

use CtCt\Components\Account\AccountInfo;
use CtCt\Components\Contacts\ContactList;
use CtCt\Components\Contacts\Contact;
use Ctct\Components\Activities\ExportContacts;
use Ctct\Components\Activities\AddContacts;


interface ConstantContactManagerInterface {

  public function getAccountInfo(AccountInterface $account);

  public function putAccountInfo(AccountInterface $account, AccountInfo $account_info);

  public function getContactLists(AccountInterface $account);

  public function getContactListsOptions(AccountInterface $account, $empty);

  public function getContactList(AccountInterface $account, $listid);

  public function createContactList(AccountInterface $account, ContactList $list);

  public function putContactList(AccountInterface $account, ContactList $list);

  public function deleteContactList(AccountInterface $account, $listid);

  public function getContacts(AccountInterface $account, $listid = NULL);

  public function getContact(AccountInterface $account, $contactId);

  public function getContactStatuses();

  public function createContact(AccountInterface $account, Contact $contact, $actionByContact);

  public function putContact(AccountInterface $account, Contact $contact, $actionByContact);

  public function unsubscribeContact(AccountInterface $account, $contactId);

  public function getListsForContact(array $lists, AccountInterface $account);

  public function getListIdsForContact(array $lists);

  public function exportContactsActivity(AccountInterface $account, ExportContacts $exportContacts);

  public function importContactsActivity(AccountInterface $account, AddContacts $addContacts);

  public function importContactsActivityFromFile(AccountInterface $account, $fileName, $fileLocation, $lists);

}
