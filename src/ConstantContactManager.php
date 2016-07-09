<?php

namespace Drupal\constant_contact;

use Ctct\Components\Activities\Activity;
use Ctct\ConstantContact;
use CtCt\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\Contact;
use Drupal\constant_contact\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
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

  /**
   * Cache expires after 1hr.
   */
  const CC_CACHE_EXPIRE = 3600;

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
  }

  /**
   * Get account information from cache or Constant Contact webservice.
   *
   * @param \Drupal\constant_contact\AccountInterface $account
   * @return \Ctct\Components\Account\AccountInfo|null
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getAccountInfo(AccountInterface $account) {
    $api_key = $account->getApiKey();
    $cid = 'constant_contact:account:' . $api_key;

    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact($api_key);
      try {
        $data = $cc->accountService->getAccountInfo($account->getAccessToken());
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data instanceof AccountInfo) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + self::CC_CACHE_EXPIRE);
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
      $cc = new ConstantContact($api_key);
      try {
        $data = $cc->accountService->getAccountInfo($access_token);
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data instanceof AccountInfo) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + self::CC_CACHE_EXPIRE);
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
    $cc = new ConstantContact($account->getApiKey());
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
  public function getActivities(AccountInterface $account) {
    $api_key = $account->getApiKey();
    $cid = 'constant_contact:activities:' . $api_key;

    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact($api_key);
      try {
        $data = $cc->activityService->getActivities($account->getAccessToken());
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data[0] instanceof Activity) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + self::CC_CACHE_EXPIRE);
      }
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $activityId
   * @return mixed|null
   */
  public function getActivity(AccountInterface $account, $activityId) {
    $returnActivity = NULL;
    $activities = $this->getActivities($account);
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
  public function getContactLists(AccountInterface $account) {
    $api_key = $account->getApiKey();
    $cid = 'constant_contact:contact_lists:' . $api_key;

    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact($api_key);
      try {
        $data = $cc->listService->getLists($account->getAccessToken());
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data[0] instanceof ContactList) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + self::CC_CACHE_EXPIRE);
      }
    }
    return $data;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @return array
   */
  public function getContactListsOptions(AccountInterface $account, $empty = FALSE) {
    $options = [];
    if ($empty) {
      $options[0] = t('- Select -');
    }

    $lists = $this->getContactLists($account);
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
  public function getContactList(AccountInterface $account, $listid) {
    $contact_list = NULL;
    $lists = $this->getContactLists($account);
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
  public function putContactList(AccountInterface $account, ContactList $list) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->listService->updateList($account->getAccessToken(), $list);
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
  public function getContacts(AccountInterface $account, $listid = NULL) {
    $api_key = $account->getApiKey();
    $cid = 'constant_contact:contacts:' . $api_key;
    $cid .= ($listid) ? ':' . $listid : '';

    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact($api_key);

      try {
        $data = ($listid) ? $cc->contactService->getContactsFromList($account->getAccessToken(), $listid)->results :
          $cc->contactService->getContacts($account->getAccessToken())->results;
      } catch (Exception $e) {
        $data = FALSE;
      }

      if ($data[0] instanceof Contact) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + self::CC_CACHE_EXPIRE);
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
  public function createContactList(AccountInterface $account, ContactList $list) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->listService->addList($account->getAccessToken(), $list);
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
  public function deleteContactList(AccountInterface $account, $listid) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->listService->deleteList($account->getAccessToken(), $listid);
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
  public function getContact(AccountInterface $account, $contactId) {
    $returnContact = NULL;
    $contacts = $this->getContacts($account);
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
  public function createContact(AccountInterface $account, Contact $contact, $actionByContact = FALSE) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->contactService->addContact($account->getAccessToken(), $contact, $actionByContact);
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
  public function putContact(AccountInterface $account, Contact $contact, $actionByContact = FALSE) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->contactService->updateContact($account->getAccessToken(), $contact, $actionByContact);
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
  public function exportContactsActivity(AccountInterface $account, ExportContacts $exportContacts) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->activityService->addExportContactsActivity($account->getAccessToken(), $exportContacts);
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
  public function importContactsActivity(AccountInterface $account, AddContacts $addContacts) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->activityService->createAddContactsActivity($account->getAccessToken(), $addContacts);
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
  public function importContactsActivityFromFile(AccountInterface $account, $fileName, $fileLocation, $lists) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->activityService->createAddContactsActivityFromFile($account->getAccessToken(), $fileName, $fileLocation, $lists);
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
  public function unsubscribeContact(AccountInterface $account, $contactId) {
    $cc = new ConstantContact($account->getApiKey());
    try {
      $data = $cc->contactService->unsubscribeContact($account->getAccessToken(), $contactId);
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
          $listNames = $this->getListsForContact($object->{$field}, $account);
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
  public function getListsForContact(array $lists, AccountInterface $account) {
    $listNames = [];
    $listIds = $this->getListIdsForContact($lists);
    $contactLists = $this->getContactLists($account);
    foreach ($contactLists as $contactList) {
      if (in_array($contactList->id, $listIds)) {
        $listNames[] = $contactList->name;
      }
    }
    return $listNames;
  }

}
