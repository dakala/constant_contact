<?php

namespace Drupal\constant_contact;

use Ctct\Components\Activities\Activity;
use Ctct\ConstantContact;
use CtCt\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\Contact;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\constant_contact\Entity\Account as CCAccount;
use Ctct\Components\Activities\ExportContacts;
use Ctct\Components\Activities\AddContacts;
use Exception;

/**
 * Class ConstantContactManager
 * @package Drupal\constant_contact
 */
class ConstantContactManager implements ConstantContactManagerInterface {

  /**
   * Group settings config object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Entity manager service
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Database connection
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  protected $ccAccount;

  /**
   * Name of cache bin service to use.
   */
  const CC_CACHE_BIN = 'constant_contact';

  /**
   * Constructs the group manager service.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The current database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The translation manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityManagerInterface $entity_manager, Connection $connection) {
    $this->configFactory = $config_factory;
    $this->entityManager = $entity_manager;
    $this->connection = $connection;
    $this->setCCAccount();
  }

  public function setCCAccount() {
    if (empty($this->ccAccount)) {
      $accounts = CCAccount::loadMultiple();
      if ($accounts) {
        $this->ccAccount = current($accounts);
      }
    }
  }

  public function getCCAccount() {
    return $this->ccAccount;
  }

  /**
   * @deprecated
   *
   * Get account information from cache or Constant Contact webservice.
   */
  public function getAccountInfo() {
    $cid = 'constant_contact:account:' . $this->ccAccount->id();
    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
      try {
        $data = $cc->accountService->getAccountInfo(CCContactManager::CTCT_API_SECRET);
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data instanceof AccountInfo) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + $this->configFactory->get('constant_contact.settings')->get('cc_cache_expire_default'));
      }
    }
    return $data;
  }

  /**
   * @param $api_key
   * @param $access_token
   * @return \Ctct\Components\Account\AccountInfo|null
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getAccountInfoFromData($api_key, $access_token) {
    $data = NULL;
    $cid = 'constant_contact:account:' . $api_key;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
      try {
        $data = $cc->accountService->getAccountInfo($access_token);
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data instanceof AccountInfo) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + $this->configFactory->get('constant_contact.settings')->get('cc_cache_expire_default'));
      }
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param \CtCt\Components\Account\AccountInfo $account_info
   * @return \Ctct\Components\Account\AccountInfo
   * @throws \Ctct\Exceptions\CtctException
   */
  public function putAccountInfo(AccountInterface $account, AccountInfo $account_info) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->accountService->updateAccountInfo($account->getAccessToken(), $account_info);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @return array|bool
   */
  public function getActivities() {
    $data = NULL;
    if(empty($this->ccAccount)) {
      return $data;
    }
    $api_key = $this->ccAccount->id();
    $cid = 'constant_contact:activities:' . $api_key;

    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
      try {
        $data = $cc->activityService->getActivities($this->ccAccount->getAccessToken());
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data[0] instanceof Activity) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + $this->configFactory->get('constant_contact.settings')->get('cc_cache_expire_default'));
      }
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $activityId
   * @return mixed|null
   */
  public function getActivity($activityId) {
    $returnActivity = NULL;
    $activities = $this->getActivities();
    foreach ($activities as $activity) {
      if ($activity->id == $activityId) {
        $returnActivity = $activity;
        break;
      }
    }
    return $returnActivity;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @return array|null
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getContactLists() {
    $data = NULL;
    if(empty($this->ccAccount)) {
      return $data;
    }
    $api_key = $this->ccAccount->id();
    $cid = 'constant_contact:contact_lists:' . $api_key;

    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
      try {
        $data = $cc->listService->getLists($this->ccAccount->getAccessToken());
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data[0] instanceof ContactList) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + $this->configFactory->get('constant_contact.settings')->get('cc_cache_expire_default'));
      }
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @return array
   */
  public function getContactListsOptions($empty = FALSE) {
    $options = [];
    if ($empty) {
      $options[0] = t('- Select -');
    }

    $lists = $this->getContactLists();
    foreach($lists as $list) {
      $options[$list->id] = $list->name;
    }
    return $options;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $listid
   * @return mixed|null
   */
  public function getContactList($listid) {
    $contact_list = NULL;
    $lists = $this->getContactLists();
    foreach ($lists as $list) {
      if ($list->id == $listid) {
        $contact_list = $list;
        break;
      }
    }
    return $contact_list;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param \Ctct\Components\Contacts\ContactList $list
   * @return \Ctct\Components\Contacts\ContactList
   * @throws \Ctct\Exceptions\CtctException
   */
  public function putContactList(ContactList $list) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->listService->updateList($this->ccAccount->getAccessToken(), $list);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param null $listid
   * @return \Ctct\Components\ResultSet
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getContacts($listid = NULL) {
    $data = NULL;
    if(empty($this->ccAccount)) {
      return $data;
    }
    $cid = 'constant_contact:contacts:' . $this->ccAccount->id();
    $cid .= ($listid) ? ':' . $listid : '';

    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);

      try {
        $data = ($listid) ? $cc->contactService->getContactsFromList($this->ccAccount->getAccessToken(), $listid)->results :
          $cc->contactService->getContacts($this->ccAccount->getAccessToken())->results;
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data[0] instanceof Contact) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + $this->configFactory->get('constant_contact.settings')->get('cc_cache_expire_default'));
      }
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param \Ctct\Components\Contacts\ContactList $list
   * @return \Ctct\Components\Contacts\ContactList
   * @throws \Ctct\Exceptions\CtctException
   */
  public function createContactList(ContactList $list) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->listService->addList($this->ccAccount->getAccessToken(), $list);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $listid
   * @return \Ctct\Components\Contacts\ContactList
   * @throws \Ctct\Exceptions\CtctException
   */
  public function deleteContactList($listid) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->listService->deleteList($this->ccAccount->getAccessToken(), $listid);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $contactId
   * @return \Ctct\Components\Contacts\Contact
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getContact($contactId) {
    $returnContact = NULL;
    $contacts = $this->getContacts($this->ccAccount);
    foreach ($contacts as $contact) {
      if ($contact->id == $contactId) {
        $returnContact = $contact;
        break;
      }
    }
    return $returnContact;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param \Ctct\Components\Contacts\Contact $contact
   * @param bool $actionByContact
   * @return \Ctct\Components\Contacts\Contact
   * @throws \Ctct\Exceptions\CtctException
   */
  public function createContact(Contact $contact, $actionByContact = FALSE) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->contactService->addContact($this->ccAccount->getAccessToken(), $contact, $actionByContact);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param \Ctct\Components\Contacts\Contact $contact
   * @param bool $actionByContact
   * @return \Ctct\Components\Contacts\Contact
   * @throws \Ctct\Exceptions\CtctException
   */
  public function putContact(Contact $contact, $actionByContact = FALSE) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);

    try {
      $data = $cc->contactService->updateContact($this->ccAccount->getAccessToken(), $contact, $actionByContact);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param \Ctct\Components\Activities\ExportContacts $exportContacts
   * @return array
   * @throws \Ctct\Exceptions\CtctException
   */
  public function exportContactsActivity(ExportContacts $exportContacts) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->activityService->addExportContactsActivity($this->ccAccount->getAccessToken(), $exportContacts);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param \Ctct\Components\Activities\AddContacts $addContacts
   * @return array
   * @throws \Ctct\Exceptions\CtctException
   */
  public function importContactsActivity(AddContacts $addContacts) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->activityService->createAddContactsActivity($this->ccAccount->getAccessToken(), $addContacts);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $fileName
   * @param $fileLocation
   * @param $lists
   * @return \Ctct\Components\Activities\Activity
   * @throws \Ctct\Exceptions\CtctException
   */
  public function importContactsActivityFromFile($fileName, $fileLocation, $lists) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->activityService->createAddContactsActivityFromFile($this->ccAccount->getAccessToken(), $fileName, $fileLocation, $lists);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $contactId
   * @throws \Ctct\Exceptions\CtctException
   */
  public function unsubscribeContact($contactId) {
    $cc = new ConstantContact(CCContactManager::CTCT_API_KEY);
    try {
      $data = $cc->contactService->unsubscribeContact($this->ccAccount->getAccessToken(), $contactId);
    } catch (Exception $e) {
      $data = FALSE;
    }
    return $data;
  }

  /**
   * Prepare object for display.
   *
   * @param $object
   * @param string $empty
   * @param \Drupal\constant_contact\AccountInterface|NULL $account
   * @return array
   */
  public function convertObjectToArray($object, AccountInterface $account = NULL, $empty = '-') {
    $fields = $this->getFields($object);
    $array = [];
    foreach ($fields as $field) {
      $array[$this->normalizeFieldName($field)] = $this->getFieldValue($object, $field, $account, $empty);
    }
    return $array;
  }

  /**
   * Get object properties as array.
   *
   * @param $object
   * @return array
   */
  public function getFields($object) {
    return array_keys(get_object_vars($object));
  }

  /**
   * @param $object
   * @param $field
   * @return string
   */
  public function getFieldValue($object, $field, AccountInterface $account = NULL, $empty = '-') {
    // strings, integers and booleans
    switch (TRUE) {
      case is_string($object->{$field}):
        return !empty($object->{$field}) ? $object->{$field} : $empty;

      case is_int($object->{$field}):
        return $object->{$field};

      case is_bool($object->{$field}):
        return $object->{$field} ? 'Y' : 'N';
    }

    switch ($field) {
      // activity
      case 'errors':
      case 'warnings':
        if (!empty($object->{$field})) {
          $value = [
            'data' => [
              '#theme' => 'item_list',
              '#items' => $object->{$field},
            ]
          ];
        }
        else {
          $value = $empty;
        }
        return $value;

      // contact
      case 'email_addresses':
        if (!empty($object->{$field})) {
          $email_addresses = [];
          foreach ($object->{$field} as $email) {
            $email_addresses[] = $email->email_address;
          }

          $value = [
            'data' => [
              '#theme' => 'item_list',
              '#items' => $email_addresses,
            ]
          ];
        }
        else {
          $value = $empty;
        }
        return $value;

      case 'addresses': // TODO:
      case 'notes': // TODO:
      case 'lists':
        if (!empty($object->{$field})) {
          $listNames = $this->getListsForContact($object->{$field});
          $value = [
            'data' => [
              '#theme' => 'item_list',
              '#items' => $listNames,
            ]
          ];
        }
        else {
          $value = $empty;
        }
        return $value;

      case 'custom_fields': // TODO:
      default:
        return $object->{$field};
    }
  }

  /**
   * Prepare field name for display as label.
   *
   * @param $string
   * @return mixed
   */
  public function normalizeFieldName($string) {
    $string = ucfirst(strtolower($string));
    return str_replace('_', ' ', $string);
  }

  public function normalizeFieldNames($object) {
    return array_map([$this, 'normalizeFieldName'], $this->getFields($object));
  }

  public function normalizeFieldNamesArray($array) {
    return array_map([$this, 'normalizeFieldName'], $array);
  }

  /**
   * @TODO: Move to settings.
   *
   * @return array
   */
  public function getContactStatuses() {
    return [
      'ACTIVE',
      'UNCONFIRMED',
      'OPTOUT',
      'REMOVED',
      'NON_SUBSCRIBER',
      'VISITOR',
      'TEMP_HOLD',
    ];
  }

  /**
   * @param $lists
   * @return array
   */
  public function getListIdsForContact(array $lists) {
    $listIds = [];
    foreach ($lists as $list) {
      $listIds[] = $list->id;
    }
    return $listIds;
  }

  /**
   * @param array $lists
   * @param \Drupal\constant_contact\AccountInterface $account
   * @return array
   */
  public function getListsForContact(array $lists) {
    $listNames = [];
    $listIds = $this->getListIdsForContact($lists);
    $contactLists = $this->getContactLists();
    foreach ($contactLists as $contactList) {
      if (in_array($contactList->id, $listIds)) {
        $listNames[] = $contactList->name;
      }
    }
    return $listNames;
  }

  /**
   * Get all available contact lists.
   * @return array
   */
  public function getAllContactLists() {
    $lists = [];
    $accounts = CCAccount::loadMultiple();
    foreach ($accounts as $account) {
       $lists += $this->getContactListsOptions();
    }

    return $lists;
  }

  /**
   *  Get the Constant Account users sign up to at registration.
   *
   * @return \Drupal\constant_contact\AccountInterface
   */
  public function getSignupAccount() {
    $accounID = \Drupal::config('constant_contact.settings')->get('cc_signup_account');
    return CCAccount::load($accounID);
  }

  /**
   * Get array of all Constant Contact accounts keyed by ID.
   *
   * @param null $accounts
   * @param bool $empty
   * @return array
   */
  public function getAccountOptions($accounts = NULL, $empty = TRUE) {
    $options = [];
    if ($accounts === NULL || !is_array($accounts)) {
      if ($empty) {
        $options[0] = t('- Select -');
      }

      foreach ($accounts as $account) {
        $options[$account->id()] = $account->getApplication();
      }
    }

    return $options;
  }

  public function deleteAccountEntities() {
    $accounts = CCAccount::loadMultiple();
    if ($accounts) {
      foreach ($accounts as $account) {
        $account->delete();
      }
    }
  }

}
