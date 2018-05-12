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