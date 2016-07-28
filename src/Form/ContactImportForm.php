<?php
/**
 * Created by PhpStorm.
 * User: dakala
 * Date: 04/07/2016
 * Time: 10:00
 */

namespace Drupal\constant_contact\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\constant_contact\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Ctct\Components\Activities\AddContacts;
use Ctct\Components\Activities\Activity;
use Ctct\Components\Activities\AddContactsImportData;
use Drupal\constant_contact\ConstantContactManager;

class ContactImportForm extends FormBase{

  /** @var \Drupal\constant_contact\ConstantContactManagerInterface */
  protected $constantContactManager;

  /** @var  \Drupal\constant_contact\AccountInterface $account */
  protected $account;

  /** @var  int $listid */
  protected $listid;

  /**
   * ContactImportForm constructor.
   *
   * @param \Drupal\constant_contact\ConstantContactManagerInterface $constant_contact_manager
   */
  public function __construct(ConstantContactManagerInterface $constant_contact_manager) {
    $this->constantContactManager = $constant_contact_manager;
    $this->account = $this->constantContactManager->getCCAccount();
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @return static
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('constant_contact.manager'));
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'contact-import';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $listid = NULL) {
    $this->listid = $listid;

    $form['upload'] = [
      '#type' => 'file',
      '#title' => $this->t('List of subscribers'),
      '#description' => $this->t('Allowed type: @extensions.', ['@extensions' => 'csv']),
    ];

    $form['contacts'] = [
      '#type' => 'textarea',
      '#placeholder' => "John Smith jsmith@example.com\r\nmarysmith@abc.com",
      '#title' => $this->t('Paste names & emails'),
      '#rows' => '10',
      '#cols' => '20',
      '#description' => $this->t('Enter names and emails, or just emails. Press Enter after each address.'),
    ];

    $form['lists'] = [
      '#type' => 'select',
      '#options' => $this->constantContactManager->getContactListsOptions(FALSE),
      '#default_value' => !empty($listid) ? $listid : '',
      '#title' => $this->t('Lists'),
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    $form['actions'] = ['#type' => 'actions'];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#attributes' => ['class' => ['button', 'button--primary']],
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => [[$this, 'standardCancel']],
      '#validate' => [],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // @todo: validate email addresses


      // Handle file uploads.
    $validators = ['file_validate_extensions' => ['csv']];
    // Check for a new uploaded CSV file.
    $file = file_save_upload('upload', $validators, FALSE, 0);
    if (isset($file)) {
      // File upload was attempted.
      if ($file) {
        // Put the temporary file in form_values so we can save it on submit.
        $form_state->setValue('upload', $file);
      }
      else {
        // File upload failed.
        $form_state->setErrorByName('upload', $this->t('The CSV file could not be uploaded.'));
      }
    }
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $returnedReport = NULL;
    $message = '';
    $values = $form_state->getValues();

    if (strlen($values['contacts'])) {
      $contacts = [];
      $new_contacts = $this->getNewContacts($values['contacts']);

      foreach ($new_contacts as $contact) {
        $contacts[] = new AddContactsImportData($contact);
      }

      $lists = array_values($values['lists']);
      $column_names = ['EMAIL', 'FIRST NAME', 'LAST NAME'];

      $addContacts = new AddContacts($contacts, $lists, $column_names);
      $returnedReport = $this->constantContactManager->importContactsActivity($addContacts);
    }
    else if (!empty($values['upload'])) {
      $file = $values['upload'];
      $returnedReport = $this->constantContactManager->importContactsActivityFromFile($file->getFilename(), $file->getFileUri(), implode(', ', $form_state->getValue('lists')));
    }

    if ($returnedReport instanceof Activity) {
      $this->logger('constant_contact')->info('Import contacts activity created by %user', ['%user' => \Drupal::currentUser()->getAccountName()]);
      $message = $this->t('Import contacts activity created.');
    }
    else {
      $message = $this->t('Import activity creation failed.');
    }

    // Clear CC cache.
    \Drupal::cache(ConstantContactManager::CC_CACHE_BIN)->deleteAll();

    drupal_set_message($message);

    $this->standardCancel($form, $form_state);
  }

  /**
   * Submit handler for cancel button
   */
  public function standardCancel($form, FormStateInterface $form_state) {
    $form_state->setRedirect('constant_contact.contacts.collection', ['listid' => $this->listid]);
  }

  /**
   * @param $string
   * @return array
   */
  public function getNewContacts($string) {
    $contacts = [];
    $rows = explode("\r\n", $string);
    foreach ($rows as $line) {
      $formatted_row = [];
      $row = explode(' ', $line);
      foreach ($row as $key => $value) {
        if (strpos($value, '@') !== FALSE) {
          $formatted_row['email_addresses'] = [$value];
          unset($row[$key]);
        }
      }

      if(count($row)) {
        $formatted_row['first_name'] = array_shift($row);
      }

      if(count($row)) {
        $formatted_row['last_name'] = array_shift($row);
      }
      $contacts[] = $formatted_row;
    }
    return $contacts;
  }

}
