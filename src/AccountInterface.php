<?php

namespace Drupal\constant_contact;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a constant contact account entity.
 */
interface AccountInterface extends ConfigEntityInterface {

  public function getExpiresIn();

  public function getTokenType();

  public function getAccessToken();

  public function getMessage();
}
