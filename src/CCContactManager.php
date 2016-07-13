<?php
/**
 * Created by PhpStorm.
 * User: dakala
 * Date: 12/07/2016
 * Time: 15:57
 */

namespace Drupal\constant_contact;

use Ctct\Components\Activities\Activity;
use Ctct\ConstantContact;
use CtCt\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\Contact;
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

class CCContactManager implements CCContactManagerInterface {

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
   * @inheritdoc
   */
  public function getFieldMappings() {
    $mappings = [];
    foreach (\Drupal::service('entity_field.manager')
               ->getFieldDefinitions('profile', $this->configFactory->get('constant_contact.settings')->get('cc_profile_type')) as $field_definition) {
      if ($field_definition instanceof FieldConfigInterface) {
        if (!empty($third_party_settings = $field_definition->get('third_party_settings')) && !empty($third_party_settings['constant_contact'])) {
          $field_name = $field_definition->get('field_name');
          $field = $third_party_settings['constant_contact']['cc_contact'];
          if ($field == 'address') {
            $array['field'] = $field;
            $array['type'] =  $third_party_settings['constant_contact']['cc_address_type'];
            // overwrite the field_name
            $mappings['addresses'][] = [$third_party_settings['constant_contact']['cc_address_type'] => $field_name];
          }
          else {
            $mappings[$field] = $field_name;
          }
        }
      }
    }

    return $mappings;
  }

  /**
   * @inheritdoc
   */
  public function getAddressFieldMappings() {
    $address_provider = $this->configFactory->get('constant_contact.settings')->get('cc_address_provider');
    $contact_address_info = \Drupal::moduleHandler()->invokeAll('contact_address_info');
    return array_flip($contact_address_info[$address_provider]);
  }

  /**
   * @inheritdoc
   */
  public function createContact(UserAccountInterface $account, array $values) {
    $contact = [];

    $entity_key = 'entity_' . $this->configFactory->get('constant_contact.settings')->get('cc_profile_type');
    $mappings = $this->getFieldMappings();
    foreach ($mappings as $field => $mapping) {
      if ($field == 'addresses') {
        foreach ($mapping as $address_field) {
          foreach($address_field as $type => $value)  {
            $contact[$field][] = $this->getAddress($type, $values[$entity_key][$value][0]);
          }
        }
      }
      else {
        $contact[$field] = !empty($values[$entity_key][$mapping]) ? $values[$entity_key][$mapping][0]['value'] : '';
      }
    }

    $contact['email_addresses'][] = $this->getEmailAddress($account);
    $contact['lists'] =  array_keys($values['cc_contact_lists']);
    $contact['source'] = $this->configFactory->get('constant_contact.settings')->get('cc_source');
    $contact['source_details'] = $this->configFactory->get('constant_contact.settings')->get('cc_source_details');

    $returnContact =  (empty($response = $this->isContact($account->getEmail())))  ?
      $this->createNewContact($contact, $account) : $this->updateOldContact($contact, $response, $account);

    return $returnContact;
  }

  /**
   * @inheritdoc
   */
  public function getEmailAddress(UserAccountInterface $account) {
    $current_user = \Drupal::currentUser();
    return [
      'email_address' => $account->getEmail(),
      'opt_in_source' =>  $current_user->getEmail() == $account->getEmail() ? 'ACTION_BY_VISITOR' : 'ACTION_BY_OWNER',
    ];
  }

  /**
   * @inheritdoc
   */
  public function getAddress($type, array $value) {
    $address = [];
    $mappings = $this->getAddressFieldMappings();
    foreach ($mappings as $field => $mapping) {
      $address[$field] = $value[$mapping];
    }
    $address['address_type'] = $type;

    return $address;
  }

  public function isContact($email) {
    $account = \Drupal::service('constant_contact.manager')->getConstantContactAccount();
    $cc = new ConstantContact($account->getApiKey());
    return $cc->contactService->getContacts($account->getApiKey(), array("email" => $email));
  }

  public function createNewContact(array $contact, UserAccountInterface $account) {
    return \Drupal::service('constant_contact.manager')->createContact(
      \Drupal::service('constant_contact.manager')->getConstantContactAccount(),
      Contact::create($contact),
      \Drupal::currentUser()->getEmail() == $account->getEmail()
    );
  }

  public function updateOldContact(array $values, $response, UserAccountInterface $account) {
    $contact = $response->results[0];
    if($contact instanceof Contact) {
       foreach($values as $field => $value) {
         if($field != 'email_addresses') {
           $contact->{$field} = $value;
         }
       }
      return \Drupal::service('constant_contact.manager')->putContact(
        \Drupal::service('constant_contact.manager')->getConstantContactAccount(),
        $contact,
        \Drupal::currentUser()->getEmail() == $account->getEmail()
      );
    }
  }

}
