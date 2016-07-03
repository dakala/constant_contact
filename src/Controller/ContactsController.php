<?php

namespace Drupal\constant_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\constant_contact\AccountInterface;
use Ctct\ConstantContact;
use GuzzleHttp;
use Symfony\Component\HttpFoundation\Request;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Drupal\Core\Url;

class ContactsController extends ControllerBase {

  /**
   * @param \Drupal\constant_contact\AccountInterface $constant_contact_account
   * @param null $listid
   * @return mixed
   */
  public function index(AccountInterface $constant_contact_account, $listid = NULL) {
    $contacts = \Drupal::service('constant_contact.manager')->getContacts($constant_contact_account, $listid);

    $fields = $this->getFields();
    $header = \Drupal::service('constant_contact.manager')->normalizeFieldNamesArray($fields);
    $header[] = $this->t('Operations');

    $rows = [];
    foreach ($contacts as $contact) {
     $row = [];
      foreach ($fields as $field) {
        $row[] = $this->getFieldValue($contact, $field);
      }

      $links['view'] = array(
        'title' => $this->t('View'),
        'url' => Url::fromRoute('constant_contact.contact.view', ['constant_contact_account' => $constant_contact_account->id(), 'id' => $contact->id]),
      );

      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('constant_contact.contact.edit', ['constant_contact_account' => $constant_contact_account->id(), 'id' => $contact->id]),
      ];
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('constant_contact.contact.delete', ['constant_contact_account' => $constant_contact_account->id(), 'id' => $contact->id]),
      );

      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }

    $build['contacts'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No contacts available. <a href=":link">Add contact</a>.', [':link' => Url::fromRoute("constant_contact.contact_list.add", [
        'constant_contact_account' => $constant_contact_account->id(),
      ])]),
    ];

    return $build;
  }

  /**
   * @param \Drupal\constant_contact\AccountInterface $constant_contact_account
   * @param $id
   * @return array
   */
  public function view(AccountInterface $constant_contact_account, $id) {
    $contact = \Drupal::service('constant_contact.manager')->getContact($constant_contact_account, $id);

    return $build = [
      '#theme' => 'cc_contact',
      '#fields' => \Drupal::service('constant_contact.manager')->convertObjectToArray($contact, $constant_contact_account),
    ];
  }

  /**
   * @return array
   */
  public function getFields() {
    return [
      'first_name',
      'last_name',
      'status',
      'confirmed',
      'email_addresses',
      'lists',
      'created_date'
    ];
  }

  /**
   * @param $contact
   * @param $field
   * @return array|int|string
   */
  public function getFieldValue($contact, $field) {
    switch($field) {
      case 'confirmed':
        return $contact->{$field} ? 'Y' : 'N';

      case 'lists':
        // @TODO: Lists have no name.
        return count($contact->{$field});

      case 'email_addresses':
        $email_addresses = [];
        foreach ($contact->{$field} as $email) {
          $email_addresses[] = $email->email_address;
        }

        return [
          'data' => [
            '#theme' => 'item_list',
            '#items' => $email_addresses,
          ]
        ];

      default:
        return $contact->{$field};
    }
  }




  public function todo() {
    return ['#type' => 'markup', '#markup' => __FUNCTION__];

  }
}
