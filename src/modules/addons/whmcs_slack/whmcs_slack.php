<?php

use WHMCS\Database\Capsule;

/**
 *
 * WHMCS Slack
 *
 * A WHMCS addon for sending messages to Slack based on WHMCS hooks.
 *
 * @author     Markus Tenghamn <m@rkus.io>
 * @copyright  Copyright (c) Markus Tenghamn 2018
 * @license    MIT License (https://github.com/markustenghamn/WHMCS-Slack/blob/master/LICENSE)
 * @version    $Id$
 * @link       https://github.com/markustenghamn/WHMCS-Slack
 *
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/slack_request.php';

function whmcs_slack_getmodulename()
{
    return "WHMCS Slack";
}

function whmcs_slack_config()
{
    $configarray = array(
        "name" => whmcs_slack_getmodulename(),
        "description" => "This plugin will post to slack when events happen in your WHMCS installation. Remember to configure the plugin.",
        "version" => "3.0",
        "author" => "Markus Tenghamn",
        "language" => "english",
        "fields" => array(
            "token" => array("FriendlyName" => "Token", "Type" => "text", "Size" => "25", "Description" => "Get the token from your <a href=\"https://slack.com/apps/manage/custom-integrations\" target=\"_blank\">Slack integrations page</a>.", "Default" => "Slack token",),
            "botname" => array("FriendlyName" => "Post as", "Type" => "text", "Size" => "25", "Description" => "Usually the name of a bot", "Default" => "WHMCS bot",),
        ));
    return $configarray;
}

function whmcs_slack_getbaseurl()
{
    $base = __DIR__;
    $base = str_replace("modules/addons/whmcs_slack", "", $base);
    return $base;
}

function whmcs_slack_activate()
{
    if (function_exists("full_query")) {
        $val = full_query('SELECT 1 FROM mod_whmcs_slack_hooks');
    }

    if($val !== FALSE)
    {
        return array(
            'status' => 'success',
            'description' => 'WHMCS Slack has been activated.'
        );
    }

    try {
    	// 2.0 -> 3.0 check goes here because entire addon changes namespace and table names
		if (Capsule::schema()->hasTable('mod_anveto_slack_hooks')) {
			Capsule::schema()->rename('mod_anveto_slack_hooks', 'mod_whmcs_slack_hooks');
		}
        full_query("CREATE TABLE mod_whmcs_slack_hooks (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY, hook VARCHAR(200) NOT NULL, channel VARCHAR(200) NOT NULL, text VARCHAR(200), created_at TIMESTAMP)");
        return array('status' => 'success', 'description' => 'WHMCS Slack has been activated.');
    } catch (ErrorException $e) {
        return array('status' => 'error', 'description' => 'Could not activate WHMCS Slack.');
    }


}

function whmcs_slack_deactivate()
{
    return array('status' => 'success', 'description' => 'Thanks for using ' . whmcs_slack_getmodulename());
}

function whmcs_slack_upgrade($vars)
{
    $version = $vars['version'];

}

function whmcs_slack_output($vars)
{
    global $hooksArray;
    global $_POST;
    $table = "mod_whmcs_slack_hooks";

    if (isset($_POST['hook'])) {
        //New hook is being added
        $hook = (string)$_POST['hook'];
        if (isset($hooksArray[$hook])) {
            $values = array("hook" => $_POST['hook'], "channel" => $hooksArray[$hook]['channel'], "text" => $hooksArray[$hook]['default'], "created_at" => date("Y-m-d H:i:s"));
            $newid = insert_query($table, $values);
        } else {
            echo "<b>Error:</b> not a valid hook.";
        }
    } else if (isset($_POST['updateHook'])) {
        $update = array("channel"=>$_POST['channel'], "text"=>$_POST['text']);
        $where = array("id"=>$_POST['updateHook']);
        update_query($table,$update,$where);

        echo '<div class="successbox">';
	    echo '<strong><span class="title">Hook update</span></strong>';
	    echo '<br>';
	    echo 'Your changes have been saved';
	    echo '</div>';

    } else if (isset($_POST['testHook'])) {
		$d = Capsule::table('mod_whmcs_slack_hooks')->select('id','hook','channel','text')->where('id', $_POST['testHook'])->first();
	    $message = "";
	    if (isset($vars['params'])) {
		    $vars = $vars['params'];
	    }
	    if (isset($d->text)) {
		    $message = $d->text;
		    foreach ($vars as $key=>$val) {
			    if (strpos($message, '{'.$key.'}') !== false) {
				    $message = str_replace('{'.$key.'}', $val, $message);
			    }
		    }
	    }
	    if ($message != "") {
		    whmcs_slack_send_request($message, $d->channel);
	    }

	    echo '<div class="successbox">';
	    echo '<strong><span class="title">Hook test message sent</span></strong>';
	    echo '<br>';
	    echo 'A test message was sent to '.htmlentities($d->channel).'. Please make sure you have set your integration token in the addon configuration and that the bot has been added to the channel.';
	    echo '</div>';

    } else if (isset($_POST['deleteHook'])) {
        if (is_numeric($_POST['deleteHook'])) {
            full_query("DELETE FROM ".$table." WHERE id = ".$_POST['deleteHook']);
        }

	    echo '<div class="successbox">';
	    echo '<strong><span class="title">Hook deleted</span></strong>';
	    echo '<br>';
	    echo 'The hook has been deleted.';
	    echo '</div>';
    }

    echo '<form method="post" action="" style="background-color:#efefef;padding:15px">';
    echo '<div id="formatting" style="float:right"><a href="https://api.slack.com/docs/message-formatting" target="_blank">Learn about Slack message formatting</a></div>';
    echo '<h2 style="float:left;margin-top:5px">Create New Hook:</h2>&nbsp;';
    echo '<select name="hook">';
    foreach ($hooksArray as $k=>$h) {
        echo '<option value="'.$k.'">'.$k.'</option>';
    }
    echo '</select>&nbsp;';
    echo '<button type="submit" class="btn btn-primary btn-sm"><i class="fa fa-plus-circle"></i> Add Hook</button>';
    echo '</form>';
    
    foreach ( Capsule::table($table)->select('id','hook','channel','text')->get() as $d ) {
        echo '<div style="border:1px dashed #efefef;padding:15px 20px;">';
        echo '<form method="post" action="" style="display:inline">';
        echo '<h2>'.$d->hook.'</h2>';
        echo '<b>Description: </b>'.$hooksArray[$d->hook]['description'].'<br/>';
        echo '<input type="hidden" name="updateHook" value="'.$d->id.'">';
        echo '<small>CHANNEL:</small>&nbsp;';
        echo '<b><input type="text" name="channel" value="'.$d->channel.'"></b><br/>';
        echo '<small>MESSAGE:</small><br/>';
        echo '<textarea cols="50" rows="3" name="text" style="width:100%">'.$d->text.'</textarea><br/>';
        echo '<b>Available parameters: </b>'.implode(", ", $hooksArray[$d->hook]['args']).'<br/>';
        echo '<button type="submit" class="button btn btn-sm btn-default"><i class="fa fa-refresh"></i> Update Hook</button>';
        echo '</form>';

	    echo '<form method="post" action="" style="display:inline">';
	    echo '<input type="hidden" name="testHook" value="'.$d->id.'">';
	    echo '<button type="submit" class="button btn btn-sm btn-primary"><i class="fa fa-share-square"></i> Test Hook</button>';
	    echo '</form>';

        echo '<form method="post" action="" style="display:inline">';
        echo '<input type="hidden" name="deleteHook" value="'.$d->id.'">';
        echo '<button type="submit" class="button btn btn-sm btn-danger"><i class="fa fa-trash"></i> Delete Hook</button>';
        echo '</form>';
        echo '<br/>';
        echo '</div>';
    }



}

function whmcs_slack_sidebar($vars)
{

    $modulelink = $vars['modulelink'];
    $version = $vars['version'];
    $option1 = $vars['option1'];
    $option2 = $vars['option2'];
    $LANG = $vars['_lang'];

    $sidebar = '<span class="header"><img src="images/icons/addonmodules.png" class="absmiddle" width="16" height="16" />' . whmcs_slack_getmodulename() . '</span>
    <ul class="menu">
        <li>Version: ' . $version . '</li>
    </ul>';
    return $sidebar;

}