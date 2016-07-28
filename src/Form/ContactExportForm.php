<?php
namespace Drupal\constant_contact\Form;

use Drupal\constant_contact\AccountInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\constant_contact\ConstantContactManagerInterface;
use Ctct\Components\Activities\ExportContacts;


class ContactExportForm extends FormBase {

  /** @var \Drupal\constant_contact\ConstantContactManagerInterface */
  protected $constantContactManager;

  /** @var  \Drupal\constant_contact\AccountInterface $account */
  protected $account;

  /** @var  int $listid */
  protected $listid;

  /**
   * ContactExportForm constructor.
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
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'contact-export';
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
    $this->account = $this->constantContactManager->getCCAccount();
    $this->listid = $listid;

    /** Although (lists) this is an array, only one listId is supported. Specifies the
     * contact list to export by listId or by one of the following system
     * generated list names:
     * - active
     * - opted-out
     * - removed
     */
    $form['lists'] = [
      '#type' => 'select',
      '#options' => $this->getListOptions(TRUE),
      '#default_value' => !empty($listid) ? $listid : '',
      '#title' => $this->t('Lists'),
      '#required' => TRUE,
    ];

    $form['column_names'] = [
      '#type' => 'select',
      '#options' => $this->getColumnNames(),
      '#title' => $this->t('Column names'),
      '#size' => 10,
      '#multiple' => TRUE,
      '#required' => TRUE,
    ];

    $form['sort_by'] = [
      '#type' => 'select',
      '#options' => $this->getSortBy(),
      '#title' => $this->t('Sort by'),
      '#required' => TRUE,
    ];

    $file_types = ['CSV', 'TXT'];
    $form['file_type'] = [
      '#type' => 'select',
      '#options' => array_combine($file_types, $file_types),
      '#title' => $this->t('File type'),
      '#required' => TRUE,
    ];

    $form['export_date_added'] = [
      '#type' => 'select',
      '#options' => ['N', 'Y'],
      '#title' => $this->t('Export date added'),
    ];

    $form['export_added_by'] = [
      '#type' => 'select',
      '#options' => ['N', 'Y'],
      '#title' => $this->t('Export added by'),
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
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $exportContacts = new ExportContacts((array) $form_state->getValue('lists'));
    $exportContacts->file_type = $form_state->getValue('file_type');
    $exportContacts->sort_by = $form_state->getValue('sort_by');
    $exportContacts->export_date_added = (bool) $form_state->getValue('export_date_added');
    $exportContacts->export_added_by = (bool) $form_state->getValue('export_added_by');
    $exportContacts->column_names = array_values($form_state->getValue('column_names'));

    $reports = $this->constantContactManager->exportContactsActivity($exportContacts);

    if ($reports) {
      $this->logger('constant_contact')->info('Export contacts activity created by %user', ['%user' => \Drupal::currentUser()->getAccountName()]);
      $message = $this->t('Export contacts activity created.');
    }
    else {
      $message = $this->t('Export activity operation failed.');
    }

    drupal_set_message($message);

    $this->standardCancel($form, $form_state);
  }

  /**
   * Submit handler for cancel button
   */
  public function standardCancel($form, FormStateInterface $form_state) {
    $form_state->setRedirect('constant_contact.contacts.collection', ['listid' => $this->listid]);
  }

  public function getColumnNames() {
    $columns = [
      "Email",
      "First Name",
      "Last Name",
      "Job Title",
      "Company Name",
      "Work Phone",
      "Home Phone",
      "Address Line 1",
      "Address Line 2",
      "Address Line 3",
      "City",
      "State",
      "Country",
      "Zip/Postal Code",
      "Custom field 1",
      "Custom field 2",
      "Custom field 3",
      "Custom field 4",
      "Custom field 5",
      "Custom field 6",
      "Custom field 7",
      "Custom field 8",
      "Custom field 9",
      "Custom field 10",
      "Custom field 11",
      "Custom field 12",
      "Custom field 13",
      "Custom field 14",
      "Custom field 15",
    ];

    return array_combine($columns, $columns);
  }

  public function getSortBy() {
    $sortBy = [
      'EMAIL_ADDRESS',
      'DATE_DESC',
    ];

    return array_combine($sortBy, $sortBy);
  }

  public function getListOptions($empty = TRUE, $include_system = TRUE) {
    $system = ['active', 'opted-out', 'removed'];
    $options = $this->constantContactManager->getContactListsOptions($empty);

    if ($include_system) {
      $options += array_combine($system, array_map('strtoupper', $system));
    }
    return $options;
  }
}
