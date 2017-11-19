# AmoClient

Integrate AMO login into MediaWiki

## Features

 * Integrates AMO Login with MediaWiki through OpenID Connect
 * Uses the 'type' that AMO Login provides (student/teacher) to assign the user a group

## Development on Linux
To take advantage of this automation, use the Makefile: `make help`. To start,
run `make install` and follow the instructions.

## Development on Windows
Since you cannot use the `Makefile` on Windows, do the following:

  * Install PHP composer
  * (Optional) Install nodejs and npm
  * Change to the extension's directory
  * (Optional) `npm install`
  * `composer install`

Once set up, running `composer test` will run automated code checks.
Optionally you can run `npm test`.

## Installation
Configure this extension in LocalSettings.php by giving proper values to the following variables
```php
$wgAmoLoginClientRemoteURL      = 'https://login.amo.rocks/oauth/authorize';
$wgAmoLoginClientRemoteTokenURL = 'https://login.amo.rocks/oauth/token';
$wgAmoLoginClientTeachersOnly   = false; // Should only teachers be able to login? (Only teachers can edit pages)
$wgAmoLoginClientClientID       = -1; // Change -1 to your app id that you get from login.amo.rocks
$wgAmoLoginClientClientSecret   = '<secret>'; // Change <secret> to your app secret that you get from login.amo.rocks

wfLoadExtension( 'AmoClient' );
```