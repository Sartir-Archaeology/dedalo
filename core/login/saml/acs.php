<?php
// config include file
	include dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config_saml.php';

/**
 * SAML assertion response.
 *
 * The URL of this file will have been given during the SAML authorization.
 * After a successful authorization, the browser will be directed to this
 * link where it will send a certified response via $_POST.
 */

// debug
	error_log(" SAML acs... ");

$start_time=start_time();

// test
	/*
	$attributes['urn:oid:1.3.6.1.4.1.5923.1.1.1.6'] = ['33333333P']; // forced test

	// Code. Is mapped from SAML response attribute name defined in config like 'code' => 'urn:oid:1.3.4.1.47.1.5.1.8'
		$code_attr_name = SAML_CONFIG['code'];
		$code           = $attributes[$code_attr_name];

	// Login_SAML
		$response = login::Login_SAML(array(
			'code' => $code
		));

		if ($response->result===true) {

			$total = exec_time_unit($start_time,'ms')." ms";
			echo " User was saml logged successfully. ".$total;
			debug_log(__METHOD__." User was saml logged successfully. ".$total, logger::ERROR);

			#header("Location: ".DEDALO_ROOT_WEB);

		}else{

			echo $response->msg;
		}
		exit();
		*/

// login v3.0
	try {
		if (isset($_POST['SAMLResponse'])) {

			$samlSettings = new OneLogin\Saml2\Settings($saml_settings);
			$samlResponse = new OneLogin\Saml2\Response($samlSettings, $_POST['SAMLResponse']);
			if ($samlResponse->isValid()) {

				$make_login = true;
				if ($make_login!==true) {

					// Debug verification
						echo 'You are: ' . $samlResponse->getNameId() . '<br>';
						$attributes = $samlResponse->getAttributes();
						if (!empty($attributes)) {
							echo 'You have the following attributes:<br>';
							echo '<table><thead><th>Name</th><th>Values</th></thead><tbody>';
							foreach ($attributes as $attributeName => $attributeValues) {
								echo '<tr><td>' . htmlentities($attributeName) . '</td><td><ul>';
								foreach ($attributeValues as $attributeValue) {
									echo '<li>' . htmlentities($attributeValue) . '</li>';
								}
								echo '</ul></td></tr>';
							}
							echo '</tbody></table>';
						}
				}else{

					// Login into Dédalo. Credentials are all correct, enter as registered logged user

						// Code. Is mapped from SAML response attribute name defined in config like 'code' => 'urn:oid:1.3.4.1.47.1.5.1.8'
							$attributes 	= $samlResponse->getAttributes();
							$code_attr_name = SAML_CONFIG['code'];
							$code           = $attributes[$code_attr_name];
							$client_ip 		= get_client_ip();
							error_log("SAMLResponse code: ".print_r($code, true).", client_ip: ".print_r($client_ip, true));

						// Login_SAML
							$response = login::Login_SAML((object)[
								'code' => $code
							]);

							if ($response->result===true) {

								$total = exec_time_unit($start_time,'ms')." ms";
								debug_log(__METHOD__
									." SAML user code: ".print_r($code, true)." [$client_ip] was logged successfully. Time: ".$total
									, logger::WARNING
								);

							}else{

								debug_log(__METHOD__
									. " Invalid Login_SAML response " . PHP_EOL
									. ' response: ' . to_string($response)
									, logger::ERROR
								);
							}

							header("Location: ".DEDALO_ROOT_WEB);
							exit();
				}

			}else{
				// Response is received, but validation process failed
				echo 'Invalid SAML Response';
			}
		}else{
			// Any pot SAMLResponse var is received
			echo 'No SAML Response found in POST.';
		}
	}catch (Exception $e) {
		// Error in saml response manager
		echo 'Invalid SAML Response (2): ' . $e->getMessage();
	}
