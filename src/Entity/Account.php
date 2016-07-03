<?php

namespace Drupal\constant_contact\Entity;

use Drupal\constant_contact\AccountInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Account configuration entity.
 *
 * @ConfigEntityType(
 *   id = "constant_contact_account",
 *   label = @Translation("Constant Contact account"),
 *   class = "Drupal\constant_contact\Entity\Account",
 *   handlers = {
 *     "form" = {
 *        "default" = "Drupal\constant_contact\AccountForm",
 *        "delete" = "Drupal\constant_contact\Form\AccountDeleteForm"
 *     },
 *     "list_builder" = "Drupal\constant_contact\AccountListBuilder",
 *   },
 *   admin_permission = "administer constant contact",
 *   config_prefix = "account",
 *   entity_keys = {
 *     "id" = "api_key",
 *     "label" = "application",
 *   },
 *   links = {
 *     "add-form" = "/admin/config/constant_contact/account/add",
 *     "edit-form" = "/admin/config/constant_contact/account/{constant_contact_account}/edit",
 *     "delete-form" = "/admin/config/constant_contact/account/{constant_contact_account}/delete",
 *     "collection" = "/admin/config/constant_contact/account",
 *   },
 *   config_export = {
 *     "api_key",
 *     "application",
 *     "secret",
 *     "access_token",
 *   }
 * )
 */
class Account extends ConfigEntityBase implements AccountInterface {

  /**
   * The machine name of this node type.
   *
   * @var string
   */
  protected $api_key;

  /**
   * The human-readable name of the node type.
   *
   * @var string
   */
  protected $application;

  /**
   * A brief description of this node type.
   *
   * @var string
   */
  protected $description;

  /**
   * Help information shown to the user when creating a Node of this type.
   *
   * @var string
   */
  protected $secret;

  /**
   * Default value of the 'Create new revision' checkbox of this node type.
   *
   * @var string
   */
  protected $access_token;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->api_key;
  }

  public function label() {
    return $this->application;
  }

  public function getSecret() {
    return $this->secret;
  }

  public function getAccessToken() {
    return $this->access_token;
  }
}
