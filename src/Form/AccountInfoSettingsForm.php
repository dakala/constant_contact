<?php

namespace Drupal\constant_contact\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure RSS settings for this site.
 */
class AccountInfoSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'constant_contact_account_info_settings';
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
    $cc_config = $this->config('constant_contact.settings');

    // fields.account_info:
    
//    $form['api_key'] = array(
//      '#type' => 'textfield',
//      '#default_value' => $cc_config->get('account.api_key'),
//      '#title' => t('API Key'),
//    );
//
//    $form['application'] = array(
//      '#type' => 'textfield',
//      '#title' => t('Application'),
//      '#default_value' => $cc_config->get('account.application'),
//    );
//
//    $form['secret'] = array(
//      '#type' => 'textfield',
//      '#title' => t('Secret'),
//      '#default_value' => $cc_config->get('account.secret'),
//    );
//
//    $form['access_token'] = array(
//      '#type' => 'textfield',
//      '#title' => t('Access token'),
//      '#default_value' => $cc_config->get('account.access_token'),
//    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
//    $this->config('constant_contact.settings')
//      ->set('account.api_key', $form_state->getValue('api_key'))
//      ->set('account.application', $form_state->getValue('application'))
//      ->set('account.secret', $form_state->getValue('secret'))
//      ->set('account.access_token', $form_state->getValue('access_token'))
//      ->save();

    parent::submitForm($form, $form_state);
  }

}
