<?php
/**
 * Yubikey.php -- Method to identify/authorize using a Yubikey
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

$wgYubikeyAPIKey = '';
$wgYubikeyValidationBaseURL = '';

require_once("Hooks.php");
require_once("SpecialPage.php");
if (file_exists("$IP/includes/specials/SpecialUserlogin.php")) {
  require_once("specials/SpecialUserlogin.php");
} else {
  require_once("SpecialUserlogin.php");
}
require_once("YubikeyLogin.php");
require_once("YubikeyAuth.php");

/* Special pages and their functions ------------------------------------- */

SpecialPage::AddPage(new UnlistedSpecialPage('YubikeyLogin'));
SpecialPage::AddPage(new UnlistedSpecialPage('YubikeyCreateLogin'));

function YubikeyUserlogin( $par ) {
  global $wgRequest;

  if (!empty($wgRequest->data['wpYubikey'])) {
    $wgRequest->data['wpPassword'] = $wgRequest->data['wpYubikey'];

    wfDebug("YubikeyUserLogin: request came with yubikey '" .
	    $wgRequest->data['wpYubikey'] . "'\n");
  }

  YubikeyHackReturnTo();

//  wfDebug("YubikeyUserLogin: request data:\n");
//  foreach ($_GET as $key => $value) {
//    wfDebug("GET: " . $key . " : " . $value . "\n");
//  }
//  foreach ($_POST as $key => $value) {
//    wfDebug("POST: " . $key . " : " . $value . "\n");
//  }
//  wfDebug("YubikeyUserLogin: END request data:\n");

  wfSpecialUserlogin($par);
}

function wfSpecialYubikeyLogin( $par ) {
  global $wgRequest;

  $yubikey = $wgRequest->data['wpYubikey'];

  if (!empty($yubikey) && strlen($yubikey) >= 44) {
    wfDebug("wfSpecialYubikeyUserLogin: Trying to find user identity using key '" . substr($yubikey, 0, 12) . "'\n");

    $dbr =& wfGetDB( DB_SLAVE );
    $id = $dbr->selectField(YubikeyDBTable(), 'yk_user',
			    array('yk_prefix' => substr($yubikey, 0, 12)));

    wfDebug("wfSpecialYubikeyUserLogin: Found user identity " . $id . "\n");

    if ($id) {
      $wgRequest->data['wpName'] = User::whoIs($id);
      wfDebug("wfSpecialYubikeyUserLogin: ... it mapped to the name '" .
	      $wgRequest->data['wpName'] . "\n");
    }
  }

  YubikeyUserlogin($par);
}

function wfSpecialYubikeyCreateLogin( $par ) {
  global $wgRequest;
  if (!empty($wgRequest->data['wpYubikey'])) {
    $wgRequest->data['wpRetype'] = $wgRequest->data['wpYubikey'];
  }
  YubikeyUserlogin($par);
}

/* Form hooks ------------------------------------------------------------ */

function YubikeyLoginForm( &$template ) {
  global $wgRequest;
  $template->set('subtype', $wgRequest->getText('subtype'));
  $template = new YubikeyloginTemplate( $template );
  return true;
}

function YubikeyCreateForm( &$template ) {
  $template = new YubikeycreateTemplate( $template );
  return true;
}

$wgHooks['UserLoginForm'] = array();
$wgHooks['UserLoginForm'][] = 'YubikeyLoginForm';
$wgHooks['UserCreateForm'] = array();
$wgHooks['UserCreateForm'][] = 'YubikeyCreateForm';

/* Account hooks --------------------------------------------------------- */

function YubikeyAbortNewAccount( $user, &$message ) {
  global $wgAuth;
  global $wgSharedDB, $wgDBprefix;

  $yubikey = $wgRequest->data['wpYubikey'];

  if (!empty($yubikey)) {
    if ($wgAuth->authenticate($user->getName(), $yubikey)) {
      return true;
    } else {
      $message = "Invalid key";
      return false;
    }
  }
  return true;			// mediawiki will do normal processing
}

function YubikeyAddNewAccount( $user, $byemail = false ) {
  global $wgRequest;
  global $wgSharedDB, $wgDBprefix;

  $yubikey = $wgRequest->data['wpYubikey'];

  if (!empty($yubikey)) {

    // Make it strictly Yubikey only
    $user->setPassword(null);
    $user->saveSettings();

    wfDebug("YubikeyAddNewAccount: inserting the user " . $user->getName() .
	    " into the mapping table with the prefix '" . substr($yubikey, 0, 12) . "'\n");

    $dbw =& wfGetDB( DB_MASTER );
    if ($dbw->insert(YubikeyDBTable(),
		     array('yk_user' => $user->getId(),
			   'yk_prefix' => substr($yubikey, 0, 12)))) {
      return true;
    } else {
      wfDebug("YubikeyAddNewAccount: failed!\n");
      return false;
    }
  } else {
    wfDebug("YubikeyAddNewAccount: missing key?\n");
    return false;
  }
}
function YubikeyAuthPluginSetup( &$auth ) {
  global $wgRequest;

  wfDebug("YubikeyAuthPluginSetup: replacing the normal auth plugin\n");
  $auth = new YubikeyAuth;

  return true;
}

$wgHooks['AbortNewAccount'] = array();
$wgHooks['AbortNewAccount'][] = 'YubikeyAbortNewAccount';
$wgHooks['AddNewAccount'] = array();
$wgHooks['AddNewAccount'][] = 'YubikeyAddNewAccount';
$wgHooks['AuthPluginSetup'] = array();
$wgHooks['AuthPluginSetup'][] = 'YubikeyAuthPluginSetup';

/* Login/logout hooks ---------------------------------------------------- */

function YubikeyLogoutComplete( &$user, &$inject_html = '', $old_name = '' ) {
  YubikeyHackReturnTo();
  return true;
}


$wgHooks['UserLogoutComplete'] = array();
$wgHooks['UserLogoutComplete'][] = 'YubikeyLogoutComplete';

function YubikeyLoginAuthenticateAudit( $user, $password, $retval ) {
  $retvalstring = "";
  switch($retval) {
  case LoginForm::SUCCESS:
    $retvalstring = "Success!";
    break;
  case LoginForm::NO_NAME:
    $retvalstring = "No_name";
    break;
  case LoginForm::ILLEGAL:
    $retvalstring = "Illegal";
    break;
  case LoginForm::WRONG_PLUGIN_PASS:
    $retvalstring = "Wrong_plugin_pass";
    break;
  case LoginForm::NOT_EXISTS:
    $retvalstring = "Not_exists";
    break;
  case LoginForm::WRONG_PASS:
    $retvalstring = "Wrong_pass";
    break;
  case LoginForm::EMPTY_PASS:
    $retvalstring = "Empty_pass";
    break;
  case LoginForm::RESET_PASS:
    $retvalstring = "Reset_pass";
    break;
  case LoginForm::ABORTED:
    $retvalstring = "Aborted";
    break;
  case LoginForm::CREATE_BLOCKED:
    $retvalstring = "Create_blocked";
    break;
  }
  wfDebug("YubikeyLoginAuthenticateAudit: user '" . $user->getName() .
	  "' password '" . $password .
	  "' retval '" . $retvalstring . "'\n");
  return true;
}

// SHOULD ONLY BE USED FOR DEBUGGING!
//$wgHooks['LoginAuthenticateAudit'] = array();
//$wgHooks['LoginAuthenticateAudit'][] = 'YubikeyLoginAuthenticateAudit';

/* Helpers --------------------------------------------------------------- */

function YubikeyDBTable() {
  global $wgSharedDB, $wgDBprefix;

  if (isset($wgSharedDB)) {
    return "`$wgSharedDB`.${wgDBprefix}user_yubikey";
  } else {
    return "user_yubikey";
  }
}

function YubikeyHackReturnTo() {
  global $wgRequest;

  wfDebug("YubikeyHackReturnTo: before hacking returnto: " .
	  $wgRequest->data['returnto'] . "\n");
  $wgRequest->data['returnto'] =
    Yubikey_hacks::hackurl($wgRequest->data['returnto'],
			   "Special:YubikeyCreate", "Special:User");
  $wgRequest->data['returnto'] =
    Yubikey_hacks::hackurl($wgRequest->data['returnto'],
			   "Special:Yubikey", "Special:User");
  wfDebug("YubikeyHackReturnTo: after hacking returnto: " .
	  $wgRequest->data['returnto'] . "\n");
}

/* Credits --------------------------------------------------------------- */

$wgExtensionCredits['other'][]
= array(
	'name' => 'Yubikey ',
	'version' => '0.7',
	'author' => 'Richard Levitte',
	/* 'url' => 'http://www.mediawiki.org/wiki/Extension:QISSingleSignOn', */
	'description' => 'Sign on with youur Yubikey'
	);

?>
