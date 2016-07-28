<?php


namespace Drupal\constant_contact;

use Ctct\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Activities\ExportContacts;
use Ctct\Components\Activities\AddContacts;
use Drupal\constant_contact\Entity\Account as CCAccount;


interface ConstantContactManagerInterface {

  public function getCCAccount();

  public function getAccountInfo(CCAccount $account);

  public function getAccountInfoFromData($api_key, $access_token);

  public function putAccountInfo(AccountInterface $account, AccountInfo $account_info);

  public function getAccountOptions($accounts = NULL);

  public function getContactLists();

  public function getContactListsOptions($empty);

  public function getContactList($listid);

  public function createContactList(ContactList $list);

  public function putContactList(ContactList $list);

  public function deleteContactList($listid);

  public function getContacts($listid = NULL);

  public function getContact($contactId);

  public function getContactStatuses();

  public function createContact(Contact $contact, $actionByContact);

  public function putContact(Contact $contact, $actionByContact);

  public function unsubscribeContact($contactId);

  public function getListsForContact(array $lists);

  public function getListIdsForContact(array $lists);

  public function exportContactsActivity(ExportContacts $exportContacts);

  public function importContactsActivity(AddContacts $addContacts);

  public function importContactsActivityFromFile($fileName, $fileLocation, $lists);

  public function getActivities();

  public function getActivity($activityId);

}
