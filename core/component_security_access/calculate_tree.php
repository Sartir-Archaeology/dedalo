#!/usr/bin/env php
<?php
/**
* This file calculates component_security_acces datalist tree in background.
* Receives a command argument JSON encoded array as $argv[1]
* with server environment needed for config start file:
* {
* 	"server" => [
* 		 "HTTP_HOST"	: "127.0.0.1:8443",
* 		 "REQUEST_URI"	: "DEDALO_API_URL",
* 		 "SERVER_NAME"	: "127.0.0.1"
* 	],
* 	"session_id"	: "3j4mkd21cq71fh9qp7gj1ka033",
* 	"user_id"		: "-1"
* }
*/

// data
	$data = json_decode($argv[1], true);

// server environment. Restore from command arguments
	$_SERVER = $data['server'];

// user_id
	$user_id = (int)$data['user_id'];

// lang
	$lang = $data['lang'];

// session_id. Is used mainly to verify that user is logged or not.
	// get current session id and force new session name as equal
	$session_id = $data['session_id'];
	session_id($session_id);

// unlock session. Only for read
	define('PREVENT_SESSION_LOCK', true);

// config. Starts a new session with forced id from command arguments
	include (dirname(dirname(dirname(__FILE__)))) .'/config/config.php';

// unlock session
	// session_write_close();

// actions to run
	$datalist = component_security_access::calculate_tree($user_id, $lang);

// write result to file as text
	echo json_encode($datalist);
