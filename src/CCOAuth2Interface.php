<?php

  namespace Drupal\constant_contact;

  use Symfony\Component\HttpFoundation\Request;

  interface CCOAuth2Interface {


    public function getRedirectUri($urlencode = TRUE);

  }
