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
use Ctct\Components\Account\AccountInfo;
use Ctct\Components\Contacts\ContactList;
use Ctct\Components\Contacts\Contact;
use Ctct\Components\Activities\ExportContacts;
use Ctct\Components\Activities\AddContacts;
use Ctct\Components\Contacts\EmailAddress;
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
    $now = date(DATE_ISO8601, REQUEST_TIME);

    $response = $this->isContact($account->getEmail());
    if (empty($response->results)) {
      $contact = new Contact();
      $contact->created_date = $now;
    }
    else {
      $contact = $response->results[0];
    }

    $entity_key = 'entity_' . $this->configFactory->get('constant_contact.settings')->get('cc_profile_type');
    $mappings = $this->getFieldMappings();
    foreach ($mappings as $field => $mapping) {

      switch (TRUE) {
        case ($field == 'addresses'):
          foreach ($mapping as $address_field) {
            foreach($address_field as $type => $value)  {
              if (!empty($values[$entity_key][$value][0])) {
                $contact->{$field}[] = $this->getAddress($type, $values[$entity_key][$value][0]);
              }
            }
          }
          break;

        case (strpos($field, 'CustomField') !== FALSE):
            $contact->custom_fields[] = [
              'name' => $field,
              'value' => $values[$entity_key][$mapping][0]['value'],
            ];
          break;

        default:
          if (!empty($values[$entity_key][$mapping])) {
            $contact->{$field} =  $values[$entity_key][$mapping][0]['value'];
          }
      }
    }

    $contact->status = 'ACTIVE';
    $contact->confirmed = TRUE;
    $contact->modified_date = $now;
    $contact->source = $this->configFactory->get('constant_contact.settings')->get('cc_source');
    $contact->source_details = $this->configFactory->get('constant_contact.settings')->get('cc_source_details');

    foreach (array_keys(array_filter($values['cc_contact_lists'])) as $list) {
      $contact->addList((string) $list);
    }

    $emailAddress = new \stdClass();
    $emailAddress->email_address = $account->getEmail();
    $contact->email_addresses = [$emailAddress];

    $isCurrentUser = \Drupal::currentUser()->getEmail() == $account->getEmail();
    $ccAccount = \Drupal::service('constant_contact.manager')->getSignupAccount();

    $returnContact = (!empty($response->results)) ?
      \Drupal::service('constant_contact.manager')->putContact($ccAccount, $contact, $isCurrentUser) :
      \Drupal::service('constant_contact.manager')->createContact($ccAccount, $contact, $isCurrentUser);

    if ($returnContact instanceof Contact) {
      $name = $returnContact->first_name . ' ' . $returnContact->last_name;

      if(!empty($response->results)) {
        \Drupal::service('logger.factory')->get('constant_contact')->info('Contact: %label updated by %user', [
          '%label' => $name,
          '%user' => \Drupal::currentUser()->getAccountName(),
        ]);
        $message = t('Updated contact: %label.', ['%label' => $name]);
      }
      else {
        \Drupal::service('logger.factory')->get('constant_contact')->info('Contact: %label created by %user', [
          '%label' => $name,
          '%user' => \Drupal::currentUser()->getAccountName(),
        ]);
        $message = t('Created contact: %label.', ['%label' => $name]);
      }

      // Cache is stale.
      \Drupal::cache(ConstantContactManager::CC_CACHE_BIN)->delete('constant_contact:contacts:' . $ccAccount->getApiKey());
    }
    else {
      $message = t('Contact operation failed.');
    }

    drupal_set_message($message);

    return $returnContact;
  }

  /**
   * @deprecated
   *
   * @inheritdoc
   */
  public function getEmailAddress($account) {
    $current_user = \Drupal::currentUser();
    $email = ($account instanceof UserAccountInterface) ? $account->getEmail() : $account;

    return [
      'email_address' => $email,
      'opt_in_source' =>  $current_user->getEmail() == $email ? 'ACTION_BY_VISITOR' : 'ACTION_BY_OWNER',
      'opt_in_date' => date(DATE_ISO8601, REQUEST_TIME),
    ];
  }

  /**
   * @inheritdoc
   */
  public function getAddress($type, array $value) {
    $address = [];
    $mappings = $this->getAddressFieldMappings();
    foreach ($mappings as $field => $mapping) {
      $address[$field] = !empty($value[$mapping]) ? $value[$mapping] : '';
    }
    $address['address_type'] = $type;

    return $address;
  }

  /**
   * @inheritdoc
   */
  public function isContact($email) {
    $account = \Drupal::service('constant_contact.manager')->getSignupAccount();
    $cc = new ConstantContact($account->getApiKey());
    return $cc->contactService->getContacts($account->getAccessToken(), ["email" => $email]);
  }

}
