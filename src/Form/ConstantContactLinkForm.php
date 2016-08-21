<?php

namespace Drupal\constant_contact\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\constant_contact\CCOAuth2;
use Drupal\constant_contact\Entity\Account;

class ConstantContactLinkForm extends FormBase {

  /**
   * @inheritdoc
   */
  public function getFormId() {
    return 'constant_contact_link';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $error = NULL, $error_description = NULL) {

    $config = \Drupal::config('constant_contact.settings');
    $drunonce = user_password();
    \Drupal::request()->attributes->set('drunonce', $drunonce);

    $ccOauth2 = new CCOAuth2();

    $form['logo'] = [
      '#theme'  => 'image',
      '#uri'    => drupal_get_path('module',
          'constant_contact') . '/img/logo-horizontal.png',
      '#alt'    => t('Constant Contact Logo'),
      '#width'  => 225,
      '#height' => 35,
    ];

    // check accounts.
    $accounts = Account::loadMultiple();
    if ($accounts) {
      $account = current($accounts);

      $form['link'] = [
        '#type'        => 'details',
        '#title'       => $this->t('Get Connected'),
        '#collapsible' => TRUE,
        '#open'        => TRUE,
        '#description' => '<h3>' . t('This module is connected to the ConstantContact account for :email.', [':email' => $account->getUsername()]) . '</h3>' .
                          '<p><em><b>' . t('Authorised on: :created', [':created' => \Drupal::service('date.formatter')->format($account->getCreatedAt(), 'medium')]) .'</b></em></p>' .
                          '<p>' . t('You may switch to a different ConstantContact account or deauthorise this application.')  . '</p>',
      ];

      $form['link']['actions']['deauthorise'] = [
        '#type' => 'submit',
        '#value' => t('Deauthorise application'),
        '#attributes' => ['class' => ['button', 'button--danger']],
        '#account' => $account->id(),
        '#prefix' => '<div>' . t('<a href=":cc-auth-url" class="button button--primary js-form-submit form-submit">Switch connected account</a>',
            [':cc-auth-url' => $ccOauth2->getAuthorizationUrl()]),
        '#suffix' => '</div>',
        '#submit' => ['::deAuthoriseAccountSubmit'],
      ];

    }
    else {
      $form['link'] = [
        '#type'        => 'details',
        '#title'       => $this->t('Get Connected'),
        '#collapsible' => TRUE,
        '#open'        => TRUE,
        '#description' => '<h3>' . t('This module isn\'t connected to any ConstantContact account yet.') . '</h3>' .
                          '<p>' . t('Click on the button below and you will be taken to ConstantContact website to link your account with this application.') . '</p>' .
                          '<p>' . t('<a href=":cc-auth-url" class="button button--primary js-form-submit form-submit">Authorise the application on ConstantContact.com</a>',
            [':cc-auth-url' => $ccOauth2->getAuthorizationUrl()]),
      ];

      $form['trial'] = [
        '#type'        => 'details',
        '#title'       => $this->t('Get Trial account'),
        '#collapsible' => TRUE,
        '#open'        => FALSE,
        '#description' => '<p>' . t('If you don\'t have a Constant Contact account, it is very easy to get started. Learn more about powerful email marketing, made simple. You can create professional emails that bring customers to your door.') . '</p>' .
                          '<p>' . t('<a href=":cc-trial-url" class="button button--primary js-form-submit form-submit" target="_blank">Start Your 60-day Free Trial</a>',
            [':cc-trial-url' => $config->get('cc_trial_url')]) . '&nbsp; ' .
                          t('<a href=":cc-marketing-url" class="button button--primary js-form-submit form-submit" target="_blank">Learn About E-mail Marketing</a>',
                            [':cc-marketing-url' => $config->get('cc_marketing_url')]) . '</p>',
      ];

  }
    $form['status'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Get Service Status'),
      '#collapsible' => TRUE,
      '#open'        => FALSE,
      '#description' => '<p>' . t('Check real time updates and the recent history of services availability and system performance. For additional information, or to report a problem, you may contact Support.') . '</p>' .
                        '<p>' . t('<a href=":cc-status-url" class="button button--primary js-form-submit form-submit" target="_blank">Check Constant Contact Service Status</a>',
          [':cc-status-url' => $config->get('cc_status_url')]) . '</p>',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Save form_id
    $drunonce = $form_state->getValue('form_token');
    \Drupal::request()->attributes->set('drunonce', $drunonce);

    $ccOauth2 = new CCOAuth2($this->getRequest());
    $ccOauth2->saveToken($drunonce, TRUE);
    $form_state->setResponse(new TrustedRedirectResponse($ccOauth2->getAuthorizationUrl()));
   }

  /**
   * Submit callback for de-authorising access to an account.
   *
   * We delete the configuration entity created for the access.
   */
  public function deAuthoriseAccountSubmit(array $form, FormStateInterface $form_state) {
    $id = $form_state->getTriggeringElement()['#account'];
    $account = Account::load($id);
    $account->delete();
    drupal_set_message(t('Account de-authorised successfully'));
    $form_state->setRebuild();
  }

}
