<?php
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

use WHMCS\Database\Capsule;

require_once dirname(__FILE__).'/lib/Slack.php';

function whmcs_slack_send_request($message, $channel) {
	$bottoken = "";
	$username = "";
	foreach (Capsule::table('tbladdonmodules')->select('setting', 'value')->where('module', 'anveto_slack')->get() as $r){
		if ($r->setting == "token") {
			$bottoken = $r->value;
		} else if ($r->setting == "botname") {
			$username = $r->value;
		}
	}
	$slack = new Slack($bottoken);
	$args = array('channel' => $channel, 'text' => html_entity_decode($message, ENT_QUOTES), 'username' => $username, 'as_user' => 'true');

	$result = $slack->call("chat.postMessage", $args);

	if ($result['ok'] == false) {
		$values["description"] = "Slack Error: " . $result['error'];
		$results = localAPI("logactivity", $values);
	}
}
