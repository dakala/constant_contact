<?php

namespace Drupal\constant_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\constant_contact\AccountInterface;
use Ctct\ConstantContact;
use GuzzleHttp;
use Symfony\Component\HttpFoundation\Request;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Drupal\Core\Url;

class ContactListController extends ControllerBase {

  public function index() {

    $lists = \Drupal::service('constant_contact.manager')->getContactLists();
    $lists = $this->sortLists($lists);

    $header = \Drupal::service('constant_contact.manager')->normalizeFieldNames($lists[0]);
    $header[] = $this->t('Operations');

    $fields = \Drupal::service('constant_contact.manager')->getFields($lists[0]);
    $rows = [];
    foreach ($lists as $list) {
      $row = [];

      foreach ($fields as $field) {
        $row[] = $list->{$field};
      }

      $links['contacts'] = array(
        'title' => $this->t('Manage contacts'),
        'url' => Url::fromRoute('constant_contact.contacts.collection', ['listid' => $list->id]),
      );

      $links['import'] = array(
        'title' => $this->t('Import contacts'),
        'url' => Url::fromRoute('constant_contact.contacts.import', ['listid' => $list->id]),
      );

      $links['export'] = array(
        'title' => $this->t('Export contacts'),
        'url' => Url::fromRoute('constant_contact.contacts.export', ['listid' => $list->id]),
      );

      $links['edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('constant_contact.contact_list.edit', ['listid' => $list->id]),
      ];
      $links['delete'] = array(
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('constant_contact.contact_list.delete', ['listid' => $list->id]),
      );

      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];
      $rows[] = $row;
    }
    $build['contact_lists'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No contact lists available. <a href=":link">Add contact list</a>.', [':link' => Url::fromRoute("constant_contact.contact_list.add")]),
    ];

    return $build;
  }

  /**
   * @param $lists
   * @return mixed
   */
  public function sortLists($lists) {
    // Obtain array of columns
    foreach ($lists as $key => $row) {
      $created_date[$key]  = $row->created_date;
      $name[$key] = $row->name;
    }
    array_multisort($created_date, SORT_DESC, $name, SORT_ASC, $lists);
    return $lists;
  }



  public function todo() {
    return ['#type' => 'markup', '#markup' => __FUNCTION__];

  }
}
