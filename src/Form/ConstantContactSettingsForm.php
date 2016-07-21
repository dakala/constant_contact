<?php

namespace Drupal\constant_contact\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\constant_contact\Entity\Account;

/**
 * Configure Constant Contact settings for this site.
 */
class ConstantContactSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constant_contact_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['constant_contact.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('constant_contact.settings');

    $form['subscriptions'] = [
      '#type' => 'details',
      '#title' => $this->t('Subscriptions'),
      '#collapsible' => TRUE,
      '#open' => TRUE,
    ];

    $form['subscriptions']['cc_signup_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signup title'),
      '#default_value' => $config->get('cc_signup_title'),
      '#description' => $this->t('This will appear on the registration form, if enabled and the block.'),
    ];

    $form['subscriptions']['cc_signup_description'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Signup title'),
      '#default_value' => $config->get('cc_signup_description'),
      '#description' => $this->t('This will also appear on the registration form, if enabled and the block.'),
    ];

    $ccAccounts = Account::loadMultiple();
    if (count($ccAccounts) > 1) {
      $form['subscriptions']['cc_signup_account'] = [
        '#type' => 'select',
        '#options' => \Drupal::service('constant_contact.manager')->getAccountOptions($ccAccounts),
        '#title' => t('Constant Contact Account'),
        '#default_value' => \Drupal::config('constant_contact.settings')->get('cc_signup_account'),
        '#description' => $this->t('Select the Constant Contact account that users signup to at registration.'),
      ];
    }
    else {
      $ccAccount = reset($ccAccounts);
      $form['subscriptions']['cc_signup_account'] = [
        '#type' => 'value',
        '#value' => $ccAccount->id(),
      ];
    }

    $form['subscriptions']['cc_signup_registration'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow users to subscribe to mailing lists at registration.'),
      '#default_value' => $config->get('cc_signup_registration'),
    ];

    $form['subscriptions']['cc_opt_in_default'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Opt-in users to your mailing lists by default.'),
      '#default_value' => $config->get('cc_opt_in_default'),
    ];

    $form['lists'] = [
      '#type' => 'details',
      '#title' => $this->t('Contact lists'),
      '#collapsible' => TRUE,
      '#open' => FALSE,
    ];

    $fields = ['id', 'name', 'created_date', 'modified_date', 'contact_count'];
    $form['lists']['cc_contact_list_sort_field'] = [
      '#type' => 'select',
      '#options' => array_combine($fields, $fields),
      '#title' => $this->t('Sort field'),
      '#default_value' => $config->get('cc_contact_list_sort_field'),
      '#description' => $this->t('Sort contact lists by this field.'),
    ];

    $sort_direction = ['ASC', 'DESC'];
    $form['lists']['cc_contact_list_sort_direction'] = [
      '#type' => 'select',
      '#options' => array_combine($sort_direction, $sort_direction),
      '#title' => $this->t('Sort direction'),
      '#default_value' => $config->get('cc_contact_list_sort_direction'),
    ];

    $form['contacts'] = [
      '#type' => 'details',
      '#title' => $this->t('Contacts'),
      '#collapsible' => TRUE,
      '#open' => FALSE,
    ];
    $form['contacts']['cc_profile_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Profile type'),
      '#default_value' => $config->get('cc_profile_type'),
      '#size' => 30,
      '#max_length' => 30,
      '#description' => $this->t('The profile type holding your contact details to be shared with Constant Contact.'),
    ];

    $form['contacts']['cc_address_provider'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Address provider'),
      '#default_value' => $config->get('cc_address_provider'),
      '#size' => 30,
      '#max_length' => 30,
      '#description' => $this->t('The name of the module managing addresses for contacts. e.g. SimpleAddress, Address'),
    ];

    $form['contacts']['cc_source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source'),
      '#default_value' => $config->get('cc_source'),
      '#size' => 30,
      '#max_length' => 30,
      '#description' => $this->t('Describes how the contact was added, from an application, web page, etc.'),
    ];

    $form['contacts']['cc_source_details'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source details'),
      '#default_value' => $config->get('cc_source_details'),
      '#size' => 30,
      '#max_length' => 30,
      '#description' => $this->t('Name of the application that added the contact, if contact was added using the API.'),
    ];

    $form['system'] = [
      '#type' => 'details',
      '#title' => $this->t('System'),
      '#collapsible' => TRUE,
      '#open' => FALSE,
    ];

    $fields = ['id', 'name', 'created_date', 'modified_date', 'contact_count'];
    $form['system']['cc_cache_expire_default'] = [
      '#type' => 'select',
      '#options' => array_combine($fields, $fields),
      '#title' => $this->t('Sort field'),
      '#default_value' => $config->get('cc_cache_expire_default'),
      '#description' => $this->t('Sort contact lists by this field.'),
    ];

    $period = [
      3600,
      3600 * 2,
      3600 * 3,
      3600 * 4,
      3600 * 5,
      3600 * 6,
      3600 * 10,
      3600 * 12,
      3600 * 24
    ];
    $period = array_map([\Drupal::service('date.formatter'), 'formatInterval'],
      array_combine($period, $period));
    $form['system']['cc_cache_expire_default'] = [
      '#type' => 'select',
      '#title' => t('Data cache maximum age'),
      '#default_value' => $config->get('cc_cache_expire_default'),
      '#options' => $period,
      '#description' => t('The maximum time data from Constant Contact can be cached locally.'),
    ];

    $form['system']['cc_sync_users'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Sync users with Constant Contact when they subscribe/unsubscribe.'),
      '#default_value' => $config->get('cc_sync_users'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    \Drupal::configFactory()->getEditable('constant_contact.settings')
      ->set('cc_signup_title', $values['cc_signup_title'])
      ->set('cc_signup_description', $values['cc_signup_description'])
      ->set('cc_signup_registration', $values['cc_signup_registration'])
      ->set('cc_signup_account', $values['cc_signup_account'])
      ->set('cc_opt_in_default', $values['cc_opt_in_default'])
      ->set('cc_contact_list_sort_field', $values['cc_contact_list_sort_field'])
      ->set('cc_contact_list_sort_direction', $values['cc_contact_list_sort_direction'])
      ->set('cc_profile_type', strtolower($values['cc_profile_type']))
      ->set('cc_address_provider', strtolower($values['cc_address_provider']))
      ->set('cc_source_details', strtolower($values['cc_source']))
      ->set('cc_source_details', strtolower($values['cc_source_details']))
      ->set('cc_cache_expire_default', $values['cc_cache_expire_default'])
      ->set('cc_sync_users', $values['cc_sync_users'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
