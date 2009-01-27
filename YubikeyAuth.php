<?php
/**
 * YubikeyAuth.php -- Authentication class for Yubikey
 * Copyright 2009 Yubico AB (http://www.yubico.com/)
 * By Richard Levitte <richard@levitte.org>
 *
 *  This program is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program; if not, write to the Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * @author Richard Levitte <richard@levitte.org>
 * @addtogroup Extensions
 */

if( !defined( 'MEDIAWIKI' ) ) die( -1 );

require_once("Auth/Yubico.php");
require_once("AuthPlugin.php");
require_once("YubikeyHacks.php");

class YubikeyAuth extends AuthPlugin {
  // Only authenticate yubikey users, the rest will be taken care of by
  // internal Mediawiki authentication
  function authenticate( $username, $password ) {
    global $wgYubikeyAPIId, $wgYubikeyAPIKey, $wgYubikeyValidationBaseURL;

    wfDebug("YubikeyAuth::authenticate: username '" . $username .
	    "' password '" . $password . "'\n");

    if (!Yubikey_hacks::isuserlocal( $username )) {
      $yubi = new Auth_Yubico($wgYubikeyAPIId, $wgYubikeyAPIKey);
      if ($wgYubikeyAPIKey <> "") {
	$yubi->setValidationBaseURL($wgYubikeyValidationBaseURL);
      }
      $auth = $yubi->verify($password);
      if (PEAR::isError($auth)) {
	wfDebug("Yubikey authentication failed: " . $auth->getMessage() . "\n");
	wfDebug("Debug output from server: " . $yubi->getLastResponse() . "\n");
	return false;
      } else {
	wfDebug("Yubikey authentication success!\n");
	return true;
      }
    }
    return false;
  }

  // local users are allowed to change their password
  function allowPasswordChange() {
    global $wgUser;
    return Yubikey_hacks::isuserlocal( $wgUser->getName() );
  }

  // strict when it's a yubikey user
  function strict() {
    global $wgUser;
    return !Yubikey_hacks::isuserlocal( $wgUser->getName() );
  }
}
