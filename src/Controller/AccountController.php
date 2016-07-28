<?php

namespace Drupal\constant_contact\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\constant_contact\AccountInterface;

class AccountController extends ControllerBase {

  /**
   * Page title callback.
   *
   * @param \Drupal\constant_contact\AccountInterface $constant_contact_account
   * @return string
   */
  public function title(AccountInterface $constant_contact_account) {
     return $this->t('Application: %label', array('%label' => $constant_contact_account->label()));
  }

  /**
   * Fetch and display account information.
   *
   * @param \Drupal\constant_contact\AccountInterface $constant_contact_account
   * @return array
   */
  public function accountInfo(AccountInterface $constant_contact_account) {
    $account_info = \Drupal::service('constant_contact.manager')->getAccountInfo($constant_contact_account);

    return $build = [
      '#theme' => 'cc_account_info',
      '#fields' => \Drupal::service('constant_contact.manager')->convertObjectToArray($account_info),
    ];
  }

  public function todo(AccountInterface $constant_contact_account) {
    return $build = [
      '#type' => 'markup',
      '#markup' => 'TODOL:',
    ];
  }
}
