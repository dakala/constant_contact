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
 *   admin_permission = "administer constant contact",
 *   config_prefix = "account",
 *   entity_keys = {
 *     "id" = "drunonce"
 *   },
 *   config_export = {
 *     "drunonce",
 *     "username",
 *     "access_token",
 *     "token_type",
 *     "expires_in",
 *     "created_at",
 *     "message"
 *   }
 * )
 */
class Account extends ConfigEntityBase implements AccountInterface {

  protected $id;

  protected $access_token;

  protected $token_type;

  protected $expires_in;

  protected $drunonce;

  protected $message;

  protected $username;

  protected $created_at;

  public function label() {
    return $this->drunonce;
  }

  public function getExpiresIn() {
    return $this->expires_in;
  }

  public function getTokenType() {
    return $this->token_type;
  }

  public function id() {
    return $this->drunonce;
  }

  public function getAccessToken() {
    return $this->access_token;
  }

  public function getMessage() {
    return $this->message;
  }

  public function getUsername() {
    return $this->username;
  }

  public function getCreatedAt() {
     return $this->created_at;
  }

}
