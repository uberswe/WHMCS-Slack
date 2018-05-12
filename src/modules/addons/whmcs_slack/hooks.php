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

require_once dirname(__FILE__).'/db.php';
require_once dirname(__FILE__).'/slack_request.php';

global $hooksArray;
  
foreach ( Capsule::table('mod_whmcs_slack_hooks')->select('id','hook','channel','text')->get() as $d ) {
    add_hook($d->hook,1000, function($vars) use ($d) {
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
    });
}