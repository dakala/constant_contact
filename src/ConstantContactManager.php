<?php

namespace Drupal\constant_contact;

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
    $api_key = $account->id();
    $cid = 'constant_contact:account:' . $api_key;

    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact($api_key);
      $data = $cc->accountService->getAccountInfo($account->getAccessToken());
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
    $cc = new ConstantContact($account->id());
    // @todo: error code
    return $cc->accountService->updateAccountInfo($account->getAccessToken(), $account_info);
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @return array|null
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getContactLists(AccountInterface $account) {
    $api_key = $account->id();
    $cid = 'constant_contact:contact_lists:' . $api_key;

    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact($api_key);
      $data = $cc->listService->getLists($account->getAccessToken());
      if ($data[0] instanceof ContactList) {
        \Drupal::cache(self::CC_CACHE_BIN)->set($cid, $data, REQUEST_TIME + self::CC_CACHE_EXPIRE);
      }
    }
    return $data;
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
    $cc = new ConstantContact($account->id());
    // @todo: error code
    return $cc->listService->updateList($account->getAccessToken(), $list);
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param null $listid
   * @return \Ctct\Components\ResultSet
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getContacts(AccountInterface $account, $listid = NULL) {
    $api_key = $account->id();
    $cid = 'constant_contact:contacts:' . $api_key;
    $cid .= ($listid) ? ':' . $listid : '';

    $data = NULL;
    if ($cache = \Drupal::cache(self::CC_CACHE_BIN)->get($cid)) {
      $data = $cache->data;
    }
    else {
      $cc = new ConstantContact($api_key);

      $data = ($listid) ? $cc->contactService->getContactsFromList($account->getAccessToken(), $listid)->results :
        $cc->contactService->getContacts($account->getAccessToken())->results;

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
    $cc = new ConstantContact($account->id());
    // @todo: error code
    return $cc->listService->addList($account->getAccessToken(), $list);
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $listid
   * @return \Ctct\Components\Contacts\ContactList
   * @throws \Ctct\Exceptions\CtctException
   */
  public function deleteContactList(AccountInterface $account, $listid) {
    $cc = new ConstantContact($account->id());
    // @todo: error code
    return $cc->listService->deleteList($account->getAccessToken(), $listid);
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $account
   * @param $contact_id
   * @return \Ctct\Components\Contacts\Contact
   * @throws \Ctct\Exceptions\CtctException
   */
  public function getContact(AccountInterface $account, $contact_id) {
    $cc = new ConstantContact($account->id());
    // @todo: error code
    return $cc->contactService->getContact($account->getAccessToken(), $contact_id);
  }

  /**
   * Prepare object for display.
   *
   * @param $object
   * @param string $empty
   * @return array
   */
  public function convertObjectToArray($object, $empty = '-') {
    $fields = $this->getFields($object);
    $array = [];
    foreach ($fields as $field) {
      //$value = is_string($object->{$field}) ? trim($object->{$field}) : $object->{$field};
      $value = $this->getFieldValue($object, $field);
      $array[$this->normalizeFieldName($field)] = !empty($value) ? $value : $empty;
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
  public function getFieldValue($object, $field) {
    // strings, integers and booleans
    switch (TRUE) {
      case is_string($object->{$field}) || is_int($object->{$field}):
        return trim($object->{$field});

      case is_bool($object->{$field}):
        return $object->{$field} ? 'Y' : 'N';
    }

    switch ($field) {
      case 'email_addresses':
        $email_addresses = [];
        foreach ($object->{$field} as $email) {
          $email_addresses[] = $email->email_address;
        }

        return [
          'data' => [
            '#theme' => 'item_list',
            '#items' => $email_addresses,
          ]
        ];

      // contact
      case 'addresses': // TODO:
      case 'notes': // TODO:
      case 'lists': // TODO:
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

}
