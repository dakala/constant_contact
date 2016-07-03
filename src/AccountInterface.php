<?php

namespace Drupal\constant_contact;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a constant contact account entity.
 */
interface AccountInterface extends ConfigEntityInterface {

  public function getSecret();

  public function getAccessToken();
}
