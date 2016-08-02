<?php

  namespace Drupal\constant_contact;

  use Ctct\Auth\CtctOAuth2;
  use Drupal\Core\Url;
  use Drupal\Component\Utility\Html;
  use Drupal\constant_contact\Entity\Account;
  use Symfony\Component\HttpFoundation\RedirectResponse;
  use Symfony\Component\HttpFoundation\Request;
  use Drupal\constant_contact\CCContactManager;
  use Exception;

  class CCOAuth2 extends CtctOAuth2 implements CCOAuth2Interface {

    private static $token;
    private static $error;
    private static $instance;

    /** Default URL for redirecting the OAuth request */
    const CTCT_OAUTH_URI = 'http://ctct.ijed.ltd';

    public function __construct($processResponse = TRUE) {
      $this->redirect_uri = $this->getRedirectUri(FALSE);
      parent::__construct(CCContactManager::CTCT_API_KEY, CCContactManager::CTCT_API_SECRET, $this->redirect_uri);

      if($processResponse) {
        self::processResponse();
      }
    }

    static function getInstance() {
      if(empty(self::$instance)) {
        self::$instance = new CCOAuth2(false);
      }

      return self::$instance;
    }

    static function processResponse($force = FALSE) {
      // Response
      // && self::getInstance()->verifyToken($_REQUEST['drunonce'])
      if(isset($_REQUEST['drunonce'])) {
        // Make sure we have our drunonce in the Drupal request.
        \Drupal::request()->attributes->set('drunonce', $_REQUEST['drunonce']);

        // @todo: Can we add this to entity?
        if(isset($_REQUEST['error'])) {
          return new RedirectResponse(Url::fromRoute('constant_contact.link', ['error' => $_REQUEST['error'], 'error_description' => urlencode($_REQUEST['error_description'])])->toString());
        }

        // Returned an OAuth code. Let's fetch the access token based on that code.
        $ccAccount = NULL;
        if(isset($_REQUEST['code'])) {

          // We are ready for the new entity. Remove old one first.
          \Drupal::service('constant_contact.manager')->deleteAccountEntities();

          $values['drunonce'] = $_REQUEST['drunonce'];
          $values['username'] = $_REQUEST['username'];
          $values['created_at'] = REQUEST_TIME;

          try {

            $request_code = Html::escape($_REQUEST['code']);
            $values += self::getInstance()->getAccessToken($request_code);
            $values['message'] = '';

            $ccAccount = Account::create($values)->save();

          } catch(Exception $e) {

            $values['message'] =  $e->getMessage();
            $ccAccount = Account::create($values)->save();

          }
        }

        return new RedirectResponse(Url::fromRoute('constant_contact.link', ['ccAccount' => ($ccAccount) ? $ccAccount->id : '' ])->toString());
      }
    }


    public function getRedirectUri($urlencode = TRUE) {
      // Cache the drunonce if there's one.
      $request = \Drupal::request();
      $drunonce = $request->attributes->get('drunonce');

      //$this->saveToken($drunonce, TRUE);

      $url = Url::fromUri(self::CTCT_OAUTH_URI, ['query' => [
          'drunonce'	=> $drunonce,
          'prefix'	=> $request->getScheme(),
          'domain'	=> $request->getHttpHost() . '/ccpr',
          'action'	=> 'ctct_oauth',
      ]])->toString();

      return $urlencode ? urlencode($url) : $url;
    }

    public function saveToken($drunonce, $data) {
      $cid = 'constant_contact:'. CCContactManager::CC_OAUTH_CACHE_KEY . ':' . $drunonce;
      \Drupal::cache(CCContactManager::CC_CACHE_BIN)->set($cid, $data);
    }

    /**
     * Get the token response from the cache and return the token, if exists
     *
     * The token response is cached as an array (`access_token`, `expires_in`, and `token_type`)
     *
     * @return string|boolean If token, returns token string. Otherwise, returns false.
     */
    public function getToken($drunonce, $key = 'access_token') {
      $cid = 'constant_contact:' . CCContactManager::CC_OAUTH_CACHE_KEY . ':' . $drunonce;

      $value = FALSE;
      $data = NULL;
      if ($cache = \Drupal::cache(CCContactManager::CC_CACHE_BIN)->get($cid)) {
        $data = $cache->data;
        if (is_array($data[0])) {
          if($key === 'access_token') {
            $this->token = $data[0][$key];
          }
          $value = $data[0][$key];
        }
        else {
          if($key === 'access_token') {
            $this->error = $data[0];
            $this->token = FALSE;
          }
          //$value = FALSE;
        }
      }
      return $value;
    }


    public function deleteToken($drunonce) {
      $cid = 'constant_contact:' . CCContactManager::CC_OAUTH_CACHE_KEY . ':' . $drunonce;
      if ($cache = \Drupal::cache(CCContactManager::CC_CACHE_BIN)->get($cid)) {
        \Drupal::cache(CCContactManager::CC_CACHE_BIN)->delete($cid);
      }
    }

    public function verifyToken($drunonce) {
      $cid = 'constant_contact:' . CCContactManager::CC_OAUTH_CACHE_KEY . ':' . $drunonce;
      return (\Drupal::cache(CCContactManager::CC_CACHE_BIN)->get($cid)) ? TRUE : FALSE;
    }
  }
