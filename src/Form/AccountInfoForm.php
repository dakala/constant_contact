<?php

namespace Drupal\constant_contact\Form;

use Ctct\Components\Account\AccountInfo;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Drupal\constant_contact\ConstantContactManager;
use Drupal\constant_contact\AccountInterface;

/**
 * Account information.
 */
class AccountInfoForm extends FormBase {

  /** @var  \Drupal\constant_contact\Entity\Account $account */
  protected $account;

  /** @var  \Ctct\Components\Account\AccountInfo $accountInfo */
  protected $accountInfo;

  /** @var \Drupal\constant_contact\ConstantContactManagerInterface */
  protected $constantContactManager;

  /** @var  array $fields */
  protected $fields;

  const ADDRESSES_SEPARATOR = "\r\n---------------------------------------\r\n";

  /**
   * AccountInfoForm constructor.
   *
   * @param \Drupal\constant_contact\ConstantContactManagerInterface $constant_contact_manager
   */
  public function __construct(ConstantContactManagerInterface $constant_contact_manager) {
    $this->constantContactManager = $constant_contact_manager;
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('constant_contact.manager'));
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constant_contact_account_info';
  }

  protected function setFields(array $fields) {
    $this->fields = $fields;
  }

  protected function getFields() {
    return $this->fields;
  }

  protected function setAccountInfo(AccountInfo $account_info) {
    $this->accountInfo = $account_info;
  }

  protected function getAccountInfo() {
    return $this->accountInfo;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, AccountInterface $constant_contact_account = NULL) {
    // Set variable.
    $this->account = $constant_contact_account;

    $account_info = $this->constantContactManager->getAccountInfo($constant_contact_account);
    if (!$account_info instanceof AccountInfo) {
      return $form['message'] = [
        '#type' => 'markup',
        '#markup' => t('No account info available.')
      ];
    }

    $fields = $this->constantContactManager->getFields($account_info);

    $this->setAccountInfo($account_info);
    $this->setFields($fields);

    foreach ($fields as $field) {
      if ($field != 'organization_addresses') {
        $form[$field] = [
          '#type' => 'textfield',
          '#default_value' => $account_info->{$field},
          '#title' => $this->constantContactManager->normalizeFieldName($field),
        ];
        // Company logo can't be edited.
        if ($field == 'company_logo') {
          $form[$field]['#disabled'] = TRUE;
        }
      }
      else {
        $form[$field] = [
          '#type' => 'textarea',
          '#default_value' => $this->addressesToString($account_info->{$field}),
          '#title' => $this->constantContactManager->normalizeFieldName($field),
          '#rows' => '5',
          '#cols' => '20',
        ];
      }
    }

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save account'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $updated_account_info = $this->getAccountInfo();

    foreach ($this->getFields() as $field) {
      $value = trim($form_state->getValue($field));
      $value = $field == 'organization_addresses' ? $this->addressesToArray($value) : $value;
      $updated_account_info->{$field} = $value;
    }

    $updated = $this->constantContactManager->putAccountInfo($this->account, $updated_account_info);

    if ($updated instanceof AccountInfo) {
      $this->logger('constant_contact')->info('Account: %label updated by %user', [
        '%label' => $this->account->label(),
        '%user' => \Drupal::currentUser()->getAccountName(),
      ]);

      // Cache is stale.
      \Drupal::cache(ConstantContactManager::CC_CACHE_BIN)->delete('constant_contact:account:' . $this->account->getApiKey());

      $message = $this->t('Updated account %label.', ['%label' => $this->account->label()]);
    }
    else {
      $message = $this->t('Update account failed for %label.', ['%label' => $this->account->label()]);
    }
    drupal_set_message($message);

    $form_state->setRedirect('constant_contact.account.manage', ['constant_contact_account' => $this->account->id()]);
  }

  /**
   * @param $addresses
   * @return string
   */
  protected function addressesToString($addresses) {
    $array = [];
    foreach ($addresses as $address) {
      $array[] = constant_contact_orgranization_address_to_string($address, "\r\n");
    }
    return implode(self::ADDRESSES_SEPARATOR, $array);
  }

  /**
   * @param $string
   * @return array
   */
  protected function addressesToArray($string) {
    $array = [];
    $addresses = explode(self::ADDRESSES_SEPARATOR, $string);
    foreach ($addresses as $address) {
      $array[] = constant_contact_orgranization_address_to_array($address);
    }
    return $array;
  }


}

