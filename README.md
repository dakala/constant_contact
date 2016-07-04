# Constact Contact - Drupal 8

__Note:__ This is a development version and the management of the dependencies hasn't been determined yet. If you'd like to see what it does, __try it in a local sandbox__. A number of things may change.

__Dependencies:__

 - A Constant Contact account. You can get a 60-day free trial - http://bit.ly/cctrial
 - Constant Contact PHP SDK for v2 API - https://github.com/dakala/php-sdk

__Steps:__

- Edit your composer.json:

  ```javascript
  "require": {
      ...
      "constantcontact/constantcontact": "dev-development",
      ...
  },
  ...
  ,
  "repositories": [
      {
          "type": "vcs",
          "url": "https://github.com/dakala/php-sdk.git"
      }
  ],
  ...
  ```

- Run composer update
- Get the module from https://github.com/dakala/constant_contact and install.
