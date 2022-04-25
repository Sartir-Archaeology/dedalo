<?php
// PREVENT_SESSION_LOCK
define('PREVENT_SESSION_LOCK', true);
// CONFIG
include dirname(dirname(dirname(dirname(__FILE__)))).'/config/config.php';

// close session to unlock php tread
session_write_close();

// $page_globals = new stdClass();
	// 	# version
	// 	$page_globals->dedalo_version = DEDALO_VERSION;
	// 	# lang
	// 	$page_globals->dedalo_application_lang 	= DEDALO_APPLICATION_LANG;
	// 	$page_globals->dedalo_data_lang 		= DEDALO_DATA_LANG;
	// 	$page_globals->dedalo_data_nolan 		= DEDALO_DATA_NOLAN;
	// 	# parent
	// 	$page_globals->_parent 		= isset($parent) ? (int)$parent : "";
	// 	# tipos
	// 	$page_globals->tipo 		= $tipo;
	// 	$page_globals->section_tipo = defined('SECTION_TIPO') ? SECTION_TIPO : null;
	// 	# top
	// 	$page_globals->top_tipo 	= TOP_TIPO;
	// 	$page_globals->top_id 		= TOP_ID;
	// 	# modo
	// 	$page_globals->modo 		= $modo;
	// 	# caller_tipo
	// 	$page_globals->caller_tipo 	= $caller_tipo;
	// 	# context_name
	// 	$page_globals->context_name = $context_name;
	// 	# tag_id
	// 	$page_globals->tag_id 		= isset($_REQUEST["tag_id"]) ? safe_xss($_REQUEST["tag_id"]) : "";
	// 	# user_id
	// 	$page_globals->user_id 		= isset($user_id) ? $user_id : null;
	// 	# username
	// 	$page_globals->username 	= isset($username) ? $username : null;
	// 	# full_username
	// 	$page_globals->full_username= isset($full_username) ? $full_username : null;
	// 	# is_global_admin
	// 	$page_globals->is_global_admin = (bool)$is_global_admin;
	// 	# components_to_refresh
	// 	$page_globals->components_to_refresh = [];
	// 	# portal
	// 	$page_globals->portal_tipo 			= isset($_REQUEST["portal_tipo"]) ? safe_xss($_REQUEST["portal_tipo"]) : null;
	// 	$page_globals->portal_parent 		= isset($_REQUEST["portal_parent"]) ? safe_xss($_REQUEST["portal_parent"]) : null;
	// 	$page_globals->portal_section_tipo 	= isset($_REQUEST["portal_section_tipo"]) ? safe_xss($_REQUEST["portal_section_tipo"]) : null;
	// 	# id_path
	// 	$page_globals->id_path 		= isset($_REQUEST["id_path"]) ? safe_xss($_REQUEST["id_path"]) : null;
	// 	# dedalo_protect_media_files
	// 	$page_globals->dedalo_protect_media_files 	= (defined('DEDALO_PROTECT_MEDIA_FILES') && DEDALO_PROTECT_MEDIA_FILES===true) ? 1 : 0;
	// 	# notifications
	// 	$page_globals->DEDALO_NOTIFICATIONS 	  	= defined("DEDALO_NOTIFICATIONS") ? (int)DEDALO_NOTIFICATIONS : 0;
	// 	$page_globals->DEDALO_PUBLICATION_ALERT 	= defined("DEDALO_PUBLICATION_ALERT") ? (int)DEDALO_PUBLICATION_ALERT : 0;
	// 	# float_window_features
	// 	$page_globals->float_window_features 		= json_decode('{"small":"menubar=no,location=no,resizable=yes,scrollbars=yes,status=no,width=470,height=415"}');

// page_globals
	$page_globals = (function() {

		$mode				= $_GET['m'] ?? $_GET['mode'] ?? (!empty($_GET['id']) ? 'edit' : 'list');
		$user_id			= $_SESSION['dedalo']['auth']['user_id'] ?? null;
		$username			= $_SESSION['dedalo']['auth']['username'] ?? null;
		$full_username		= $_SESSION['dedalo']['auth']['full_username'] ?? null;
		$is_global_admin	= $_SESSION['dedalo']['auth']['is_global_admin'] ?? null;
		$is_root			= $user_id==DEDALO_SUPERUSER;

		$obj = new stdClass();
			# version
			$obj->dedalo_entity		= DEDALO_ENTITY;
			# version
			$obj->dedalo_version	= DEDALO_VERSION;
			# lang
			$obj->dedalo_application_langs_default	= DEDALO_APPLICATION_LANGS_DEFAULT;
			$obj->dedalo_application_lang			= DEDALO_APPLICATION_LANG;
			$obj->dedalo_data_lang					= DEDALO_DATA_LANG;
			$obj->dedalo_data_nolan					= DEDALO_DATA_NOLAN;
			$obj->dedalo_projects_default_langs		= array_map(function($current_lang){
				$lang_obj = new stdClass();
					$lang_obj->label = lang::get_name_from_code($current_lang);
					$lang_obj->value = $current_lang;
				return $lang_obj;
			}, unserialize(DEDALO_PROJECTS_DEFAULT_LANGS));

			$obj->dedalo_image_quality_default = DEDALO_IMAGE_QUALITY_DEFAULT;

			# parent
			#$obj->_parent						= isset($parent) ? (int)$parent : '';
			# tipos
			#$obj->tipo							= $tipo;
			#$obj->section_tipo					= defined('SECTION_TIPO') ? SECTION_TIPO : null;
			#$obj->section_name					= defined('SECTION_TIPO') ? RecordObj_dd::get_termino_by_tipo(SECTION_TIPO,DEDALO_APPLICATION_LANG) : null;
			# top
			#$obj->top_tipo						= TOP_TIPO;
			#$obj->top_id						= TOP_ID;
			# modo
			$obj->mode							= isset($mode) ? $mode : null;
			# caller_tipo
			#$obj->caller_tipo					= $caller_tipo;
			# context_name
			#$obj->context_name					= $context_name;
			# tag_id
			$obj->tag_id						= isset($_REQUEST["tag_id"]) ? safe_xss($_REQUEST["tag_id"]) : "";
			# user
			$obj->user_id						= $user_id;
			$obj->username						= $username;
			$obj->full_username					= $full_username;
			# is_global_admin
			$obj->is_global_admin				= $is_global_admin;
			$obj->is_root						= $is_root;
			# components_to_refresh
			#$obj->components_to_refresh		= [];
			# portal
			#$obj->portal_tipo					= isset($_REQUEST["portal_tipo"]) ? safe_xss($_REQUEST["portal_tipo"]) : null;
			#$obj->portal_parent				= isset($_REQUEST["portal_parent"]) ? safe_xss($_REQUEST["portal_parent"]) : null;
			#$obj->portal_section_tipo			= isset($_REQUEST["portal_section_tipo"]) ? safe_xss($_REQUEST["portal_section_tipo"]) : null;
			# id_path
			#$obj->id_path						= isset($_REQUEST["id_path"]) ? safe_xss($_REQUEST["id_path"]) : null;
			# dedalo_protect_media_files
			$obj->dedalo_protect_media_files	= (defined('DEDALO_PROTECT_MEDIA_FILES') && DEDALO_PROTECT_MEDIA_FILES===true) ? 1 : 0;
			# notifications
			$obj->DEDALO_NOTIFICATIONS			= defined("DEDALO_NOTIFICATIONS") ? (int)DEDALO_NOTIFICATIONS : 0;
			$obj->DEDALO_PUBLICATION_ALERT		= defined("DEDALO_PUBLICATION_ALERT") ? (int)DEDALO_PUBLICATION_ALERT : 0;
			# float_window_features
			#$obj->float_window_features		= json_decode('{"small":"menubar=no,location=no,resizable=yes,scrollbars=yes,status=no,width=600,height=540"}');
			$obj->fallback_image				= DEDALO_CORE_URL . '/themes/default/0.jpg';
			$obj->locale						= DEDALO_LOCALE;
			// debug only
			if(SHOW_DEBUG===true) {
				$obj->dedalo_db_name	= DEDALO_DATABASE_CONN;
				$obj->pg_version		= pg_version(DBi::_getConnection())['server'];
				$obj->php_version		= PHP_VERSION .' jit:'. (int)(opcache_get_status()['jit']['enabled'] ?? false);
				$obj->php_memory		= to_string(ini_get('memory_limit'));
			}


		return $obj;
	})();

// plain global vars
	$plain_vars = [
		'DEDALO_CORE_URL'			=> DEDALO_CORE_URL,
		'DEDALO_ROOT_WEB'			=> DEDALO_ROOT_WEB,
		'DEDALO_TOOLS_URL'			=> DEDALO_TOOLS_URL,
		'SHOW_DEBUG'				=> SHOW_DEBUG,
		'SHOW_DEVELOPER'			=> SHOW_DEVELOPER,
		'DEVELOPMENT_SERVER'		=> DEVELOPMENT_SERVER,
		'DEDALO_SECTION_ID_TEMP'	=> DEDALO_SECTION_ID_TEMP,
		'USE_CDN'					=> USE_CDN,
		// DD_TIPOS . Some useful dd tipos (used in client by tool_user_admin for example)
		'DD_TIPOS' => [
			'DEDALO_SECTION_USERS_TIPO'		=> DEDALO_SECTION_USERS_TIPO,
			'DEDALO_USER_PROFILE_TIPO'		=> DEDALO_USER_PROFILE_TIPO,
			'DEDALO_USER_NAME_TIPO'			=> DEDALO_USER_NAME_TIPO,
			'DEDALO_USER_PASSWORD_TIPO'		=> DEDALO_USER_PASSWORD_TIPO,
			'DEDALO_FULL_USER_NAME_TIPO'	=> DEDALO_FULL_USER_NAME_TIPO,
			'DEDALO_USER_EMAIL_TIPO'		=> DEDALO_USER_EMAIL_TIPO,
			'DEDALO_FILTER_MASTER_TIPO'		=> DEDALO_FILTER_MASTER_TIPO,
			'DEDALO_USER_IMAGE_TIPO'		=> DEDALO_USER_IMAGE_TIPO
		]
	];

// headers
	header('Content-type: application/javascript; charset=utf-8');
	// cache optional
		$seconds_to_cache = 3600;
		$ts = gmdate("D, d M Y H:i:s", time() + $seconds_to_cache) . ' GMT';
		header("Expires: $ts");
		header("Pragma: cache");
		header("Cache-Control: max-age=$seconds_to_cache");
?>
"use strict";
const page_globals=<?php
	echo (SHOW_DEBUG===true)
		? json_encode($page_globals, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
		: json_encode($page_globals, JSON_UNESCAPED_UNICODE)
?>;
const <?php // plain_vars
echo implode(',', array_map(function ($v, $k) {
	return sprintf('%s=%s', $k, json_encode($v, JSON_UNESCAPED_SLASHES));
}, $plain_vars, array_keys($plain_vars))) .';'. PHP_EOL;
// Lang labels
include dirname(__FILE__) . '/lang/'.DEDALO_APPLICATION_LANG.'.js';
// json_elements_data array
// echo ';'.PHP_EOL.js::get_json_elements_data();