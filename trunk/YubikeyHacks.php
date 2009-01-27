<?php
/**
 * YubikeyHacks.php -- Hacks, unfortunate but needed
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

require_once("User.php");

class Yubikey_hacks {
  static function hackurl($haystack, $needle, $replacement) {
    $p = strpos($haystack, $needle);
    wfDebug("hackurl: strpos('" . $haystack . "','" . $needle . "') returned " . $p . "\n");
    if ($p === false) {
      wfDebug("hackurl: returning '" . $haystack . "' untouched\n");
      return $haystack;
    } else {
      wfDebug("hackurl: replacing '" . $needle . "' with '" . $replacement . "'\n");
      return substr_replace($haystack, $replacement, $p, strlen($needle));
    }
  }
  function isuserlocal( $username ) {
    $id = User::idFromName($username);

    wfDebug("isuserlocal: username " . $username . " has identity " . $id . "\n");

    if ($id) {
      $dbr =& wfGetDB( DB_SLAVE );
      $prefix = $dbr->selectField(YubikeyDBTable(), 'yk_prefix',
				  array('yk_user' => $id));
      if ($prefix) {
	wfDebug("isuserlocal: username " . $username . " has yubikey prefix " . $prefix . "\n");
	return false;
      } else {
	wfDebug("isuserlocal: username " . $username . " is local\n");
	return true;
      }
    }
    // Anonymous users
    return true;
  }
}

?>
