<?php

use WHMCS\Database\Capsule;

/**
 * Copyright Anveto AB
 * Author: Markus Tenghamn
 * Date: 24/03/15
 * Time: 16:47
 * This is not to be removed.
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once dirname(__FILE__).'/db.php';

global $hooksArray;
  
foreach ( Capsule::table('mod_anveto_slack_hooks')->select('id','hook','channel','text')->get() as $d ) {
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
            $args = array('channel' => $d->channel, 'text' => html_entity_decode($message, ENT_QUOTES), 'username' => $username, 'as_user' => 'true');
            
            $result = $slack->call("chat.postMessage", $args);

            if ($result['ok'] == false) {
                $values["description"] = "Slack Error: " . $result['error'];
                $results = localAPI("logactivity", $values);
            }
        }
    });
}


class Slack {
    private $api_token;
    private $api_endpoint = 'https://slack.com/api/<method>';

    function __construct($api_token){
        $this->api_token = $api_token;
    }

    public function call($method, $args = array(), $timeout = 10){
        return $this->request($method, $args, $timeout);
    }

    private function request($method, $args = array(), $timeout = 10){
        $url = str_replace('<method>', $method, $this->api_endpoint);
        $args['token'] = $this->api_token;
        if (function_exists('curl_version')){
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $args);
            $result = curl_exec($ch);
            curl_close($ch);
        } else {
            $post_data = http_build_query($args);
            $result    = file_get_contents($url, false, stream_context_create(array(
                'http' => array(
                    'protocol_version' => 1.1,
                    'method'           => 'POST',
                    'header'           => "Content-type: application/x-www-form-urlencoded\r\n" .
                        "Content-length: " . strlen($post_data) . "\r\n" .
                        "Connection: close\r\n",
                    'content'          => $post_data
                ),
            )));
        }
        return $result ? json_decode($result, true) : false;
    }
}