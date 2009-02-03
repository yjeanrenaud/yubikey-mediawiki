<?php
/**
 * YubikeyLogin.php -- Template for user login/register
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

require_once("Linker.php");
require_once("Skin.php");
require_once("SkinTemplate.php"); // To get QuickTemplate
require_once("$IP/includes/templates/Userlogin.php"); // To get UserloginTemplate and UsercreateTemplate
require_once("YubikeyHacks.php");

/**
 * HTML template for Special:Userlogin form
 * @ingroup Templates
 */
class YubikeyloginTemplate extends UserloginTemplate {
  function YubikeyloginTemplate( $template ) {
    $this->Quicktemplate();
    $this->data = $template->data;
  }

  function execute() {
    global $wgUser,$IP;
    $this->data['action'] =
      Yubikey_hacks::hackurl($this->data['action'],
			     "title=Special:User", "title=Special:Yubikey");

    if (empty($this->data['subtype'])) {
      $this->data['subtype'] = "yubikey";
    }

    $link = $this->data['link'];
    $p1 = strpos($link, '<a href="') + 9;
    $p2 = strpos($link, '">');
    $link = substr($link, $p1, $p2 - $p1);
    $link = Yubikey_hacks::hackurl($link, "type=signup", "type=login");

    if ($this->data['subtype'] == "noyubikey") {
      $this->data['link'] .=
	'</p><p id="userloginlink">' .
	'Do you have a Yubikey account? <a href="' .
	$link . '&subtype=yubikey' .
	'">Log in</a>.';
      $this->data['action'] .= '&subtype=noyubikey';
      parent::execute();
      return;
    } else {
      $this->data['link'] .=
	'</p><p id="userloginlink">' .
	'Do you have a non-Yubikey account? <a href="' .
	$link . '&subtype=noyubikey' .
	'">Log in</a>.';
      $this->data['action'] .= '&subtype=yubikey';
    }

    // Create a link to alternate login, for non-yubikey accounts

    if ($this->data['message']) {
?>
	<div class="<?php $this->text('messagetype') ?>box">
		<?php if ( $this->data['messagetype'] == 'error' ) { ?>
			<h2><?php $this->msg('loginerror') ?>:</h2>
		<?php } ?>
		<?php $this->html('message') ?>
	</div>
	<div class="visualClear"></div>
<?php } ?>

<div id="loginstart"><?php $this->msgWiki( 'loginstart' ); ?></div>
<div id="userloginForm">
  <form name="userlogin" method="post" action="<?php $this->text('action') ?>">
    <h2><?php $this->msg('login') ?></h2>
    <p id="userloginlink"><?php $this->html('link') ?></p>
    <?php $this->html('header'); /* pre-table point for form plugins... */ ?>
    <div id="userloginprompt"><?php  $this->msgWiki('loginprompt') ?></div>
    <table>
      <tr>
        <td class="mw-label">
          <label for='wpYubikey1'>Yubikey:</label>
        </td>
        <td class="mw-input">
	  <input type='password' class='loginPassword'
	  	 autocomplete="off"
	         name="wpYubikey" id="wpYubikey1"
	         tabindex="1"
	         value="" size='20'
		 style="background: url(<?php echo 'extensions/Yubikey/yubiright_16x16.gif' ?>) no-repeat; background-color: #fff; background-position: 0 50%; color: #000; padding-left: 18px;" />
        </td>
      </tr>
      <tr>
	<td></td>
	<td class="mw-submit">
	  <input type='submit' name="wpLoginattempt" id="wpLoginattempt" tabindex="2" value="<?php $this->msg('login') ?>" />
	</td>
      </tr>
    </table>
  </form>
</div>
<div id="loginend"><?php $this->msgWiki( 'loginend' ); ?></div>
<?php
  }
}

/**
 * @ingroup Templates
 */
class YubikeycreateTemplate extends QuickTemplate {
  function YubikeycreateTemplate( $template ) {
    $this->Quicktemplate();
    $this->data = $template->data;
  }
  function addInputItem( $name, $value, $type, $msg ) {
    $this->data['extraInput'][] = array(
					'name' => $name,
					'value' => $value,
					'type' => $type,
					'msg' => $msg,
					);
  }
  function execute() {
    global $wgUser,$IP;
    $this->data['action'] =
      Yubikey_hacks::hackurl($this->data['action'],
			     "title=Special:User",
			     "title=Special:YubikeyCreate");

    if ($this->data['message']) {
?>
	<div class="<?php $this->text('messagetype') ?>box">
		<?php if ( $this->data['messagetype'] == 'error' ) { ?>
			<h2><?php $this->msg('loginerror') ?>:</h2>
		<?php } ?>
		<?php $this->html('message') ?>
	</div>
	<div class="visualClear"></div>
<?php } ?>

<div id="userlogin">
  <form name="userlogin2" id="userlogin2" method="post" action="<?php $this->text('action') ?>">
    <h2><?php $this->msg('createaccount') ?></h2>
    <p id="userloginlink"><?php $this->html('link') ?></p>
    <?php $this->html('header'); /* pre-table point for form plugins... */ ?>
    <div id="userloginprompt"><?php  $this->msgWiki('loginprompt') ?></div>
    <table>
      <tr>
	<td class="mw-label"><label for='wpName2'><?php $this->msg('yourname') ?></label></td>
	<td class="mw-input">
	  <input type='text' class='loginText' name="wpName" id="wpName2"
		 tabindex="1"
		 value="<?php $this->text('name') ?>" size='20' />
	</td>
      </tr>
      <?php if( $this->data['useemail'] ) { ?>
	<tr>
	  <td class="mw-label"><label for='wpEmail'><?php $this->msg('youremail') ?></label></td>
	  <td class="mw-input">
	    <input type='text' class='loginText' name="wpEmail" id="wpEmail"
		   tabindex="2"
		   value="<?php $this->text('email') ?>" size='20' />
	    <div class="prefsectiontip">
	      <?php if( $this->data['emailrequired'] ) {
		$this->msgWiki('prefs-help-email-required');
	      } else {
		$this->msgWiki('prefs-help-email');
	      } ?>
	    </div>
	  </td>
	</tr>
      <?php } ?>
      <?php if( $this->data['userealname'] ) { ?>
	<tr>
	  <td class="mw-label"><label for='wpRealName'><?php $this->msg('yourrealname') ?></label></td>
	  <td class="mw-input">
	    <input type='text' class='loginText' name="wpRealName" id="wpRealName"
		   tabindex="3"
		   value="<?php $this->text('realname') ?>" size='20' />
	    <div class="prefsectiontip">
	      <?php $this->msgWiki('prefs-help-realname'); ?>
	    </div>
	  </td>
	</tr>
      <?php } ?>
      <?php
	$tabIndex = 4;
	if ( isset( $this->data['extraInput'] ) && is_array( $this->data['extraInput'] ) ) {
	  foreach ( $this->data['extraInput'] as $inputItem ) { ?>
	    <tr>
	      <?php
		if ( !empty( $inputItem['msg'] ) && $inputItem['type'] != 'checkbox' ) {
		  ?><td class="mw-label"><label for="<?php 
		  echo htmlspecialchars( $inputItem['name'] ); ?>"><?php
		  $this->msgWiki( $inputItem['msg'] ) ?></label><?php
		} else {
		  ?><td><?php
		}
	      ?></td>
	      <td class="mw-input">
		<input type="<?php echo htmlspecialchars( $inputItem['type'] ) ?>"
		       name="<?php echo htmlspecialchars( $inputItem['name'] ); ?>"
		       tabindex="<?php echo $tabIndex++; ?>"
		       value="<?php
				if ( $inputItem['type'] != 'checkbox' ) {
				  echo htmlspecialchars( $inputItem['value'] );
				} else {
				  echo '1';
				}
			      ?>"
		       id="<?php echo htmlspecialchars( $inputItem['name'] ); ?>"
		       <?php
			 if ( $inputItem['type'] == 'checkbox' && !empty( $inputItem['value'] ) )
			   echo 'checked="checked"'; 
		       ?> />
		<?php
		  if ( $inputItem['type'] == 'checkbox' && !empty( $inputItem['msg'] ) ) {
		    ?>
		    <label for="<?php echo htmlspecialchars( $inputItem['name'] ); ?>"><?php
		    $this->msg( $inputItem['msg'] ) ?></label><?php
		  }
		?>
	      </td>
	    </tr>
	    <?php
	  }
	}
      ?>
      <tr>
        <td class="mw-label">
          <label for='wpYubikey2'>Yubikey:</label>
        </td>
        <td class="mw-input">
	  <input type='password' class='loginPassword'
	  	 autocomplete="off"
	         name="wpYubikey" id="wpYubikey2"
	         tabindex="<?php echo $tabIndex++; ?>"
	         value="" size='20'
		 style="background: url(<?php echo 'extensions/Yubikey/yubiright_16x16.gif' ?>) no-repeat; background-color: #fff; background-position: 0 50%; color: #000; padding-left: 18px;" />
        </td>
      </tr>
      <tr>
	<td></td>
	<td class="mw-submit">
	  <input type='submit'
		 name="wpCreateaccount"
		 id="wpCreateaccount"
		 tabindex="<?php echo $tabIndex++; ?>"
		 value="<?php $this->msg('createaccount') ?>" />
	</td>
      </tr>
    </table>
  </form>
</div>
<div id="loginend"><?php $this->msgWiki( 'loginend' ); ?></div>
<?php
  }
}
