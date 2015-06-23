**WARNING**: This project has not been updated in several years and does not work with the latest MediaWiki releases.  We welcome anyone who would like to take over the project.

For Mediawiki, a validation extension.

It should be possible to use with any instance of Yubico's validation server, as long as the Mediawiki `LocalSettings.php` contains all the necessary information.

It requires a Yubikey API id, API key, and validation base URL using the variables `$wgYubikeyValidationBaseURL`, `$wgYubikeyAPIId` and `$wgYubikeyAPIKey`.
The base URL defaults to Yubico's own validation server.

![http://yubico.com/img/_press_yubikey_hand_comp_.jpg](http://yubico.com/img/_press_yubikey_hand_comp_.jpg)

**Dependencies**

[Mediawiki](http://www.mediawiki.org)

[php-yubico](http://code.google.com/p/php-yubico/)