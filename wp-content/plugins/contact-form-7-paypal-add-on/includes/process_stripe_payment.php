<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly


// listen for and process stripe charge
add_action('wp_ajax_cf7pp_stripe_charge', 'cf7pp_stripe_charge');
add_action('wp_ajax_nopriv_cf7pp_stripe_charge', 'cf7pp_stripe_charge');

function cf7pp_stripe_charge($request) {

	// make sure using POST
	if ( isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] != 'POST' && isset($_POST['stripeToken']) ) {
		return;
	}

	// verify nonce
	$nonce = sanitize_text_field($_POST['nonce']);
	$salt = wp_salt();
	if (!wp_verify_nonce($nonce,'cf7pp_'.$salt)) { die( "<span style='color: red;'>".__('Error - Security validation failed.','cf7pp')."</span>" ); }


	// get options
	$options = get_option('cf7pp_options');

	// get secret key
	$stripe_key = '';
	if ($options['mode_stripe'] == "1") {
		$stripe_key = $options['sec_key_test'];
	} else {
		$stripe_key = $options['sec_key_live'];
	}

	// currency
	if ($options['currency'] == "1") { $currency = "AUD"; }
	if ($options['currency'] == "2") { $currency = "BRL"; }
	if ($options['currency'] == "3") { $currency = "CAD"; }
	if ($options['currency'] == "4") { $currency = "CZK"; }
	if ($options['currency'] == "5") { $currency = "DKK"; }
	if ($options['currency'] == "6") { $currency = "EUR"; }
	if ($options['currency'] == "7") { $currency = "HKD"; }
	if ($options['currency'] == "8") { $currency = "HUF"; }
	if ($options['currency'] == "9") { $currency = "ILS"; }
	if ($options['currency'] == "10") { $currency = "JPY"; }
	if ($options['currency'] == "11") { $currency = "MYR"; }
	if ($options['currency'] == "12") { $currency = "MXN"; }
	if ($options['currency'] == "13") { $currency = "NOK"; }
	if ($options['currency'] == "14") { $currency = "NZD"; }
	if ($options['currency'] == "15") { $currency = "PHP"; }
	if ($options['currency'] == "16") { $currency = "PLN"; }
	if ($options['currency'] == "17") { $currency = "GBP"; }
	if ($options['currency'] == "18") { $currency = "RUB"; }
	if ($options['currency'] == "19") { $currency = "SGD"; }
	if ($options['currency'] == "20") { $currency = "SEK"; }
	if ($options['currency'] == "21") { $currency = "CHF"; }
	if ($options['currency'] == "22") { $currency = "TWD"; }
	if ($options['currency'] == "23") { $currency = "THB"; }
	if ($options['currency'] == "24") { $currency = "TRY"; }
	if ($options['currency'] == "25") { $currency = "USD"; }


	// get variables
	$token = 						sanitize_text_field($_POST['token']['id']);

	// form id
	$id = 							sanitize_text_field($_POST['id']);

	// currency
	if (empty($currency)) { 		$currency = "USD"; }

	// desc
	$desc = 						get_post_meta($id, "_cf7pp_name", true);

	//sku
	$sku = 							get_post_meta($id, "_cf7pp_id", true);

	// amount
	$amount =						get_post_meta($id, "_cf7pp_price", true);

	
	if ($currency != 'JPY') {
		// convert amount to cents
		$amount = $amount * 100;
	} else {
		$amount = (int)$amount;
	}
	
	
	// email
	$email =						sanitize_text_field($_POST['email']);
	
	if (empty($email)) {
		$email = '';
	}

	// default status
	$status = '';

	// class
	\Stripe\Stripe::setApiKey($stripe_key);

	// Create a charge: this will charge the user's card
	try {
		
		$customer = \Stripe\Customer::create(array(
			"source" 				=> $token,
			"email" 				=> $email
		));
		
		$charge = \Stripe\Charge::create(array(
			"amount" 				=> $amount, // Amount in cents
			"currency" 				=> $currency,
			//"source" 				=> $token,
			"description" 			=> $desc,
			"metadata" 				=> array("ID/SKU" => $sku),
			"customer" 				=> $customer->id,
		));
		
		
		// for debugging
		//error_log($charge);
		
		
		// result variables
		$txn_id = 				sanitize_text_field($charge->id);
		
		$status = 'completed';
		
	} catch(\Stripe\Error\Card $e) {
		// Since it's a decline, \Stripe\Error\Card will be caught
		
		$body = $e->getJsonBody();
		$err  = $body['error'];
		$reason = $err['message'];

	} catch (\Stripe\Error\RateLimit $e) {
		// Too many requests made to the API too quickly

		$body = $e->getJsonBody();
		$err  = $body['error'];
		$reason = $err['message'];


	} catch (\Stripe\Error\InvalidRequest $e) {
		// Invalid parameters were supplied to Stripe's API

		$body = $e->getJsonBody();
		$err  = $body['error'];
		$reason = $err['message'];


	} catch (\Stripe\Error\Authentication $e) {
		// Authentication with Stripe's API failed
		// (maybe you changed API keys recently)

		$body = $e->getJsonBody();
		$err  = $body['error'];
		$reason = $err['message'];


	} catch (\Stripe\Error\ApiConnection $e) {
		// Network communication with Stripe failed

		$body = $e->getJsonBody();
		$err  = $body['error'];
		$reason = $err['message'];


	} catch (\Stripe\Error\Base $e) {
		// Display a very generic error to the user, and maybe send
		// yourself an email

		$body = $e->getJsonBody();
		$err  = $body['error'];
		$reason = $err['message'];


	} catch (Exception $e) {
		// Something else happened, completely unrelated to Stripe

		$body = $e->getJsonBody();
		$err  = $body['error'];
		$reason = $err['message'];

	}


	// transaction failed
	if ($status != 'completed') {
		$status = 'failed';
		$txn_id = '';
	}

	$html_success = "
		<table>
		<tr><td>".$options['status'].": </td><td>".$options['success']."</td></tr>
		<tr><td>".$options['order']." #: </td><td>$txn_id</td></tr>
		</table>
	";

	// response array
	$response = array(
		'response'		 =>		$status,
		'html_success' =>		$html_success,
	);
	
	// reset values
	if (empty($options['session'])) {
		$session = '1';
	} else {
		$session = $options['session'];
	}

	if ($session == '1') {
		setcookie('cf7pp_stripe_email', '', time()+3600, '/', $_SERVER['HTTP_HOST']);
	} else {
		session_start();
		$_SESSION['cf7pp_stripe_email'] = '';
		session_write_close();
	}

	echo json_encode($response);

	wp_die();

}
