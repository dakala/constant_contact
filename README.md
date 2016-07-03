# Constact Contact - Drupal 8

Note: This is a development version and the management of the dependencies hasn't been determined yet. If you'd like to see what it does, please try it in a local sandbox.

Dependencies:

 - A Constant Contact account. You can get a 60-day free trial - http://bit.ly/cctrial
 - Constant Contact PHP SDK for v2 API - https://github.com/dakala/php-sdk
 - CSV data manipulation made easy in PHP - https://github.com/thephpleague/csv

Steps:

- Edit your composer.json:

  ```javascript
  "require": {
      ...
      "constantcontact/constantcontact": "dev-development",
      "league/csv": "^8.0",   
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
