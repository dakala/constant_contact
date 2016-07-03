<?php

namespace Drupal\constant_contact\ParamConverter;

use Drupal\Core\ParamConverter\ParamConverterInterface;
use Drupal\constant_contact\Entity\Account;
use Symfony\Component\Routing\Route;

class ConstantContactParamConverter implements ParamConverterInterface {

  /**
   * @inheritdoc
   */
  public function convert($value, $definition, $name, array $defaults) {
    return Account::load($value);
  }

  /**
   * @inheritdoc
   */
  public function applies($definition, $name, Route $route) {
    return (!empty($definition['type']) && $definition['type'] == 'constant_contact_account');
  }
}
