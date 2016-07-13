<?php
/**
 * Created by PhpStorm.
 * User: dakala
 * Date: 12/07/2016
 * Time: 15:56
 */

namespace Drupal\constant_contact;

use Ctct\Components\Activities\Activity;
use Ctct\ConstantContact;
use CtCt\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Contacts\EmailAddress;
use Ctct\Components\Activities\ExportContacts;
use Ctct\Components\Activities\AddContacts;
use Drupal\constant_contact\AccountInterface as CCAccountInterface;
use Drupal\Core\Session\AccountInterface as UserAccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\constant_contact\Entity\Account;
use Drupal\field\FieldConfigInterface;
use Exception;


interface CCContactManagerInterface {

  public function getFieldMappings();

  public function getAddressFieldMappings();

  public function createContact(UserAccountInterface $account, array $values);

  public function getEmailAddress($account);

  public function getAddress($type, array $value);
}
